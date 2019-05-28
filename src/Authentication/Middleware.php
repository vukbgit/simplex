<?php
declare(strict_types = 1);

namespace Simplex\Authentication;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;
use Simplex\VanillaCookieExtended;
use function Simplex\slugToPSR1Name;

/**
 * Routing Middleware that uses nikic/fastroute
 * Based on Middlewares\FastRoute with the addition of routes definition processing and additionl route parameters outside of route patterns
 *
 * @author vuk <info@vuk.bg.it>
 */
class Middleware implements MiddlewareInterface
{
    /**
    * Auth factory instance
    * @var \Aura\Auth\AuthFactory
    **/
    protected $authFactory;
    
    /**
    * @var VanillaCookieExtended
    * cookies manager
    */
    protected $cookie;
    
    private $request;
    /*
    * Authentication area
    * @var string
    */
    private $area;
    
    /*
    * @var array
    * Actions that can be called by routes
    */
    private $actions = ['sign-in','verify','sign-out'];
    
    /*
    * @var array
    * Methods that can be used to sign in
    */
    private $signInMethods = ['htpasswd'];
    
    /**
    * Constructor
    * @param VanillaCookieExtended $cookie
    */
    public function __construct(VanillaCookieExtended $cookie)
    {
        session_start();
        $this->cookie = $cookie;
    }
    
    /**
     * Sets auth factory instance
     **/
    protected function setAuthFactory()
    {
        $sessionSegment = new \Aura\Auth\Session\Segment($this->area);
        $this->authFactory = new \Aura\Auth\AuthFactory($_COOKIE, null, $sessionSegment);
    }
    
    /**
     * Process a server request and return a response.
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->request =& $request;
        //check mandatory route parameters
        $routeParameters = $request->getAttributes()['parameters'];
        $mandatoryParameters = ['area'];
        foreach ($mandatoryParameters as $parameter) {
            if(!isset($routeParameters->$parameter) || !$routeParameters->$parameter) {
                throw new \Exception(sprintf('Current route definition MUST contain a \'handler\'[1][\'authentication\']->%s parameter', $parameter));
            }
        }
        $this->area = $routeParameters->area;
        $authenticationParameters = $routeParameters->authentication;
        $mandatoryParameters = ['action', 'urls'];
        foreach ($mandatoryParameters as $parameter) {
            if(!isset($authenticationParameters->$parameter) || !$authenticationParameters->$parameter) {
                throw new \Exception(sprintf('Current route definition MUST contain a \'handler\'[1][\'authentication\']->%s parameter', $parameter));
            }
        }
        //check action
        if(!in_array($authenticationParameters->action, $this->actions)) {
            throw new \Exception(sprintf('Authentication action \'%s\' not allowed', $authenticationParameters->action));
        }
        //perform action
        $this->setAuthFactory();
        $this->{slugToPSR1Name($authenticationParameters->action, 'method')}($authenticationParameters);
        //return response
        $response = $handler->handle($request);
        //update response with authentication result
        $authenticationResult = $this->request->getAttributes()['authenticationResult']->{$this->area};
        //redirect
        if($authenticationResult->redirectTo) {
            $response = $response->withHeader('Location', $authenticationResult->redirectTo);
            $response = $response->withStatus('302');
        }
        //cookies
        if(!$authenticationResult->authenticated) {
            $this->cookie->setAreaCookie($this->area, 'authenticationReturnCode', $authenticationResult->returnCode);
        } else {
            $this->cookie->setAreaCookie($this->area, 'authenticationReturnCode', null);
        }
        //handle result
        return $response;
    }
    
    /**
     * Performs sign in
     * @param object $authenticationParameters
     * @return integer:
     * 1 = missing field
     * 2 = wrong username
     * 3 = wrong password
     * 4 = correct sign in
     */
    private function signIn(object $authenticationParameters)
    {
        $returnCode = 0;
        //get input
        $args = array(
            'username' => FILTER_SANITIZE_STRING,
            'password' => FILTER_SANITIZE_STRING
        );
        $input = filter_input_array(INPUT_POST, $args);
        $username = trim($input['username']);
        $password = trim($input['password']);
        //check input
        if(!$username || !$password) {
            //missing field(s)
            $returnCode = 1;
        } else {
            //check urls
            $mandatoryUrls = ['signInForm', 'successDefault'];
            foreach ($mandatoryUrls as $parameter) {
                if(!isset($authenticationParameters->urls->$parameter) || !$authenticationParameters->urls->$parameter) {
                    throw new \Exception(sprintf('Current route definition MUST contain a \'handler\'[1][\'authentication\']->urls->%s parameter', $parameter));
                }
            }
            //check methods
            if(!isset($authenticationParameters->signInMethods) || empty($authenticationParameters->signInMethods)) {
                throw new \Exception('Current route definition MUST contain a \'handler\'[1][\'authentication\']->signInMethods parameter and it MUST not be empty');
            }
            //check users - roles map file path
            if(!isset($authenticationParameters->usersRolesPath) || !is_file($authenticationParameters->usersRolesPath)) {
                throw new \Exception('Current route definition MUST contain a \'handler\'[1][\'authentication\']->usersRolesPath parameter and it MUST be a valid path');
            }
            //check permissions - roles map file path
            if(isset($authenticationParameters->permissionsRolesPath) && !is_file($authenticationParameters->permissionsRolesPath)) {
                throw new \Exception('Current route definition contains a \'handler\'[1][\'authentication\']->permissionsRolesPath parameter but it is NOT a valid path');
            }
            //loop methods
            foreach ($authenticationParameters->signInMethods as $method => $methodProperties) {
                //check method
                if(!in_array($method, $this->signInMethods)) {
                    throw new \Exception(sprintf('Sign in method \'%s\' not allowed', $method));
                }
                switch ($method) {
                    case 'htpasswd':
                        //check htpasswd file path
                        if(!isset($methodProperties->path)) {
                            throw new \Exception('htpasswd must have a \'path\' property with path to the htpasswd file');
                        }
                        if(!is_file($methodProperties->path)) {
                            throw new \Exception(sprintf('htpasswd \'path\' property must be a valid path to a htpasswd file'));
                        }
                        $returnCode = $this->signInWithHtpasswd($methodProperties->path, $username, $password);
                    break;
                }
            }
        }
        switch ($returnCode) {
            //success
            case 4:
                //set user role
                $this->setUserRole($authenticationParameters);
                //load role permissions
                $this->loadPermissionsRoles($authenticationParameters);
                //set authentication status
                //redirect
                $location = $this->cookie->getAreaCookie($this->area, 'signInRequestedUrl') ?? $authenticationParameters->urls->successDefault;
                $this->setAuthenticationStatus(true, 4, $location);
            break;
            //failure
            default:
                $this->setAuthenticationStatus(false, $returnCode, $authenticationParameters->urls->signInForm);
            break;
        }
        return $returnCode;
    }
    
    /**
     * Checks sign in by htpasswd method
     * @param string $pathToHtpasswdFile path to htpassword file
     * @param string $username
     * @param string $password
     * @return int return code: 1 = wrong username, 2 = wrong password, 3 = sign in correct
     **/
    protected function signInWithHtpasswd($pathToHtpasswdFile, $username, $password): int
    {
        $auth = $this->authFactory->newInstance();
        $htpasswdAdapter = $this->authFactory->newHtpasswdAdapter($pathToHtpasswdFile);
        $loginService = $this->authFactory->newLoginService($htpasswdAdapter);
        try {
            //success
            $userData = [
                'username' => $username,
                'password' => $password
            ];
            $loginService->login($auth, $userData);
            unset($userData['password']);
            $this->setUserData($userData);
            $returnCode = 4;
        } catch(\Aura\Auth\Exception\UsernameNotFound $e) {
            //wrong username
            $returnCode = 2;
        } catch(\Aura\Auth\Exception\PasswordIncorrect $e) {
            //wrong password
            $returnCode = 3;
        }
        return $returnCode;
    }
    
    /**
     * Performs verification
     * @param object $authenticationParameters
     * @return integer:
     * 1 = missing field
     * 2 = wrong username
     * 3 = wrong password
     * 4 = correct sign in
     */
    private function verify(object $authenticationParameters)
    {
        $returnCode = 0;
        //check urls
        $mandatoryUrls = ['signInForm', 'signOut'];
        foreach ($mandatoryUrls as $parameter) {
            if(!isset($authenticationParameters->urls->$parameter) || !$authenticationParameters->urls->$parameter) {
                throw new \Exception(sprintf('Current route definition MUST contain a \'handler\'[1][\'authentication\']->urls->%s parameter', $parameter));
            }
        }
        //verify
        if($this->isAuthenticated()) {
            //store userdata into request
            $auth = $this->authFactory->newInstance();
            $this->setUserData();
            $this->setAuthenticationStatus(true, 4);
        } else {
            $this->setAuthenticationStatus(false, $returnCode, $authenticationParameters->urls->signInForm);
        }
    }
    
    /**
     * Signs out
     * @param object $authenticationParameters
     **/
    private function signOut(object $authenticationParameters)
    {
        $returnCode = 0;
        //check urls
        $mandatoryUrls = ['signInForm'];
        foreach ($mandatoryUrls as $parameter) {
            if(!isset($authenticationParameters->urls->$parameter) || !$authenticationParameters->urls->$parameter) {
                throw new \Exception(sprintf('Current route definition MUST contain a \'handler\'[1][\'authentication\']->urls->%s parameter', $parameter));
            }
        }
        //sign out
        $logoutService = $this->authFactory->newLogoutService();
        $auth = $this->authFactory->newInstance();
        $logoutService->logout($auth);
        $this->setAuthenticationStatus(false, $returnCode, $authenticationParameters->urls->signInForm);
    }
    
    /**
     * Gets authentication status
     **/
    protected function isAuthenticated()
    {
        $auth = $this->authFactory->newInstance();
        //r($auth->isValid());
        return $auth->isValid();
    }
    
    /**
     * Sets authentication status into request
     **/
    protected function setAuthenticationStatus($authenticated, $returnCode = null, $redirectTo = null)
    {
        $this->request = $this->request->withAttribute(
            'authenticationResult', 
            (object) [
                $this->area => (object) [
                    'authenticated' => $authenticated,
                    'returnCode' => $returnCode,
                    'redirectTo' => $redirectTo
                ]
            ]
        );
    }
    
    /**
     * Sets user data both in session and request
     * @param array $userData: if passed it is stored into session
     **/
    protected function setUserData($userData = null)
    {
        //set into session
        if($userData) {
            $auth = $this->authFactory->newInstance();
            $auth->setUserData($userData);
        }
        //set into request
        $this->request = $this->request->withAttribute('userData', $this->getUserData());
    }
    
    /**
     * Gets user data
     **/
    protected function getUserData(): object
    {
        $auth = $this->authFactory->newInstance();
        return (object) $auth->getUserData();
    }
    
    /**
     * Sets user role from users - roles map file
     * @param object $authenticationParameters
     **/
    protected function setUserRole($authenticationParameters)
    {
        //get current userdata
        $userData = $this->getUserData();
        //get users roles
        $userRoles = require $authenticationParameters->usersRolesPath;
        //check it's an object
        if(!is_object($userRoles)) {
            throw new \Exception(sprintf('File %s must return an object', $authenticationParameters->usersRolesPath));
        }
        //check user role
        if(!isset($userRoles->{$userData->username})) {
            throw new \Exception(sprintf('A role must be assigned to user \'%s\' into file %s', $userData->username, $authenticationParameters->usersRolesPath));
        }
        //set user role
        $userData->role = $userRoles->{$userData->username};
        $this->setUserData((array) $userData);
    }
    
    /**
     * Loads permissions for roles from permissions - roles map file
     * @param object $authenticationParameters
     **/
    protected function loadPermissionsRoles($authenticationParameters)
    {
        //get current userdata
        $userData = $this->getUserData();
        //get permissions roles
        $permissionsRoles = require $authenticationParameters->permissionsRolesPath;
        //check it's an object
        if(!is_object($permissionsRoles)) {
            throw new \Exception(sprintf('File %s must return an object', $authenticationParameters->permissionsRolesPath));
        }
        //set user's role permissions
        $userPermissions = [];
        foreach ((array) $permissionsRoles as $permission => $roles) {
            if(in_array($userData->role, $roles)) {
                $userPermissions[] = $permission;
            }
        }
        $userData->permissions = $userPermissions;
        $this->setUserData((array) $userData);
    }
}
