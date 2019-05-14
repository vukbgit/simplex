<?php
declare(strict_types = 1);

namespace Simplex\Authentication;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Routing Middleware that uses nikic/fastroute
 * Based on Middlewares\FastRoute with the addition of routes definition processing and additionl route parameters outside of route patterns
 *
 * @author vuk <info@vuk.bg.it>
 */
class Middleware implements MiddlewareInterface
{
    use AuthenticationTrait;
    
    /*
    * Actions that can be called by routes
    */
    private $actions = ['login','verify','logout'];
    
    /*
    * Methods that can be used to login
    */
    private $loginMethods = ['htpasswd'];
    
    /**
     * Process a server request and return a response.
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     *
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        //check mandatory route parameters
        $authenticationParameters = $request->getAttributes()['parameters']->authentication;
        $mandatoryParameters = ['area', 'action', 'urls'];
        foreach ($mandatoryParameters as $parameter) {
            if(!isset($authenticationParameters->$parameter) || !$authenticationParameters->$parameter) {
                throw new \Exception(sprintf('Current route definition MUST contain a \'handler\'[1][\'authentication\']->%s parameter', $parameter));
            }
        }
        //check action
        if(!in_array($authenticationParameters->action, $this->actions)) {
            throw new \Exception(sprintf('Authentication action \'%s\' not allowed', $authenticationParameters->action));
        }
        //check urls
        $mandatoryUrls = ['loginForm', 'successDefault'];
        foreach ($mandatoryUrls as $parameter) {
            if(!isset($authenticationParameters->urls->$parameter) || !$authenticationParameters->urls->$parameter) {
                throw new \Exception(sprintf('Current route definition MUST contain a \'handler\'[1][\'authentication\']->urls->%s parameter', $parameter));
            }
        }
        //perform action
        $this->setAuthFactory($authenticationParameters->area);
        $returnCode = $this->{$authenticationParameters->action}($authenticationParameters);
        r($returnCode);
        exit;
        //get authentication status
        //create session segment
        if($area) {
            $sessionSegment = new Segment($area);
        } else {
            throw new \Exception('Current route definition MUST contain a \'handler\'[1][\'authentication\']->area parameter');
        }
        //create auth instance
        $authFactory = new AuthFactory($_COOKIE, null, $sessionSegment);
        $auth = $authFactory->newInstance();
        //check current authentication status
        $logStatus = $auth->getStatus();
        //call handler to get response
        $response = $handler->handle($request);
        //failure
        if($logStatus === 'ANON') {
            $failureURL = $authenticationParameters->authentication->failureURL ?? null;
            //redirect on failure
            if($failureURL) {
                $response = $response->withHeader('Location', $failureURL);
                //set status to "Found" "The requested resource resides temporarily under a different URI" (https://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html)
                $response = $response->withStatus('302');
            } else {
                throw new \Exception('Current route definition MUST contain a \'handler\'[1][\'authentication\'][\'failureURL\'] parameter');
            }
        }
        return $response;
    }
    
    /**
     * Performs login
     * @param object $authenticationParameters
     */
    private function login(object $authenticationParameters): int
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
            return 1;
        }
        //check methods
        if(!isset($authenticationParameters->loginMethods) || empty($authenticationParameters->loginMethods)) {
            throw new \Exception('Current route definition MUST contain a \'handler\'[1][\'authentication\']->loginMethods parameter and it MUST not be empty');
        }
        //loop methods
        foreach ($authenticationParameters->loginMethods as $method => $methodProperties) {
            //check method
            if(!in_array($method, $this->loginMethods)) {
                throw new \Exception(sprintf('Login method \'%s\' not allowed', $method));
            }
            /* Each method must return a code:
            * 1 = wrong username
            * 2 = wrong password
            * 3 = login correct
            */
            switch ($method) {
                case 'htpasswd':
                    //check htpasswd file path
                    if(!isset($methodProperties->path)) {
                        throw new \Exception('htpasswd must have a \'path\' property with path to the htpasswd file');
                    }
                    if(!is_file($methodProperties->path)) {
                        throw new \Exception(sprintf('htpasswd \'path\' property must be a valid path to a htpasswd file'));
                    }
                    $returnCode = $this->loginWithHtpasswd($methodProperties->path, $username, $password);
                break;
            }
        }
        //set cookies
        return $returnCode;
    }
}
