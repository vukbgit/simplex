<?php
declare(strict_types = 1);

namespace Simplex\Authentication;

/**
 * Authentication trait to perform login, logout and authentication vaerification
 * uses Aura\Auth
 */
 trait AuthenticationTrait{
     
     /**
     * Auth factory instance
     **/
     protected $authFactory;
     
     /**
      * Sets auth factory instance
      * @param string $area session segment to be used
      **/
     protected function setAuthFactory(string $area)
     {
         $sessionSegment = new \Aura\Auth\Session\Segment($area);
         $this->authFactory = new \Aura\Auth\AuthFactory($_COOKIE, null, $sessionSegment);
     }
     
     /**
      * Checks login by htpasswd method
      * @param string $pathToHtpasswdFile path to htpassword file
      * @param string $username
      * @param string $password
      * @return int return code: 1 = wrong username, 2 = wrong password, 3 = login correct
      **/
     protected function loginWithHtpasswd($pathToHtpasswdFile, $username, $password): int
     {
         $auth = $this->authFactory->newInstance();
         $htpasswdAdapter = $this->authFactory->newHtpasswdAdapter($pathToHtpasswdFile);
         $loginService = $this->authFactory->newLoginService($htpasswdAdapter);
         try {
             $loginService->login($auth, array(
                 'username' => $username,
                 'password' => $password
             ));
             $returnCode = 3;
         } catch(\Aura\Auth\Exception\UsernameNotFound $e) {
             $returnCode = 1;
         } catch(\Aura\Auth\Exception\PasswordIncorrect $e) {
             $returnCode = 2;
         }
         return $returnCode;
     }
     
     /**
      * Gets authentication status
      **/
     protected function getAuthenticationStatus()
     {
         $auth = $authFactory->newInstance();
         return $auth->getStatus();
     }
}
