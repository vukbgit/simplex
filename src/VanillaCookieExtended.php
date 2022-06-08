<?php
declare(strict_types=1);

namespace Simplex;

use CodeZero\Cookie\VanillaCookie;
use function Simplex\slugToPSR1Name;

/*
* Subclass of Vanilla Cookie (https://github.com/codezero-be/cookie) to add some functionalities
*
*/
class VanillaCookieExtended extends VanillaCookie
{
    /**
    * Sets a cookie into area cookies portion
    * @param string $area: area name
    * @param string $propertyName: name of property to be set into area cookie
    * @param mixed $propertyValue: value of property to be set into area cookie
    */
    /*public function setAreaCookie(string $area, string $propertyName, $propertyValue)
    {
        $areaCookie = $this->getAreaCookie($area);
        $areaCookie->$propertyName = $propertyValue;
        $areaCookieJson = json_encode($areaCookie);
        $this->store(
            $area,
            $areaCookieJson,
            COOKIE_DURATION,
            '/', //path
            null,   //domain
            true,   //secure
            false   //httponly
        );
        //update global cookie container so tat current script has acces to updated cookie data
        $_COOKIE[$area] = $areaCookieJson;
    }*/
    
    /**
    * Generates a new area user data key
    */
    private function generateAreaUserDataKey()
    {
      return uniqid();
    }
    
    /**
    * Sets a cookie into area cookies portion
    * @param string $area: area name
    * @param string $propertyName: name of property to be set into area cookie
    * @param mixed $propertyValue: value of property to be set into area cookie
    */
    public function setAreaCookie(string $area, string $propertyName, $propertyValue)
    {
      //get data
      $areaUserData = $this->getAreaCookie($area);
      //get key if any
      $areaUserDataKey = $this->get($area);
      if($areaUserDataKey === null) {
        $areaUserDataKey = $this->generateAreaUserDataKey();
        $this->storeAreaCookie($area, $areaUserDataKey);
      }
      //set property
      $areaUserData->$propertyName = $propertyValue;
      //store data
      $this->storeAreaUserData($area, $areaUserDataKey, $areaUserData);
    }
    
    /**
    * Stores area cookie with key
    * @param string $area: area name
    * @param string $areaUserDataKey
    */
    private function storeAreaCookie(string $area, string $areaUserDataKey)
    {
        $this->store(
            $area,
            $areaUserDataKey,
            COOKIE_DURATION,
            '/', //path
            null,   //domain
            true,   //secure
            false   //httponly
        );
        //update global cookie container so that current script has acces to updated cookie data
        $_COOKIE[$area] = $areaUserDataKey;
    }
    
    /**
    * Gets an area cookie as an object
    * @param string $area: area name
    * @param string $propertyName: optional property yo be returned
    * @return object the whole area cookie
    */
    /*public function getAreaCookie(string $area, string $propertyName = null)
    {
        $areaCookieJson = $this->get($area);
        $areaCookie = $areaCookieJson ? json_decode($areaCookieJson) : new \stdClass;
        if($propertyName) {
            return $areaCookie->$propertyName ?? null;
        }
        return $areaCookie;
    }*/
    
    /**
    * Gets an area cookie as an object
    * @param string $area: area name
    * @param string $propertyName: optional property yo be returned
    * @return object the whole area cookie
    */
    public function getAreaCookie(string $area, string $propertyName = null)
    {
        $areaCookie = $this->get($area);
        //no area cookie yet
        if($areaCookie === null) {
          return new \stdClass;
        } elseif(is_object(json_decode($areaCookie))) {
          //handle transition
          $areaUserData = json_decode($areaCookie);
          //create $areaUserDataKey
          $areaUserDataKey = $this->generateAreaUserDataKey();
          //save new light area cookie
          $this->storeAreaCookie($area, $areaUserDataKey);
          //store user data
          $this->storeAreaUserData($area, $areaUserDataKey, $areaUserData);
        } else {
          //new area cookie with just area user data key
          $areaUserData = $this->getAreaUserData($area, $areaCookie);
        } 
        if($propertyName) {
          return $areaUserData->$propertyName ?? null;
        } else {
          return $areaUserData;
        }
    }
    
    /**
    * Builds area user data file path
    * @param string $area: area name
    * @param string $areaUserDataKey
    */
    private function buildAreaUserDataPath(string $area, string $areaUserDataKey)
    {
      /*return sprintf(
        '%s/%s/userdata/%s.json',
        PRIVATE_LOCAL_DIR,
        slugToPSR1Name($area, 'class'),
        $areaUserDataKey
      );*/
      $path = sprintf(
        '%s/%s/userdata',
        PRIVATE_LOCAL_DIR,
        slugToPSR1Name($area, 'class')
      );
      //check and create
      if(!is_dir($path)) {
        mkdir($path);
      }
      return sprintf(
        '%s/%s.json',
        $path,
        slugToPSR1Name($areaUserDataKey, 'class')
      );
    }
    
    /**
    * Stores an area user data as a file
    * @param string $area: area name
    * @param string $areaUserDataKey
    * @param object $areaUserData the whole area object
    */
    private function storeAreaUserData(string $area, string $areaUserDataKey, object $areaUserData)
    {
      $areaUserDataPath = $this->buildAreaUserDataPath($area, $areaUserDataKey);
      file_put_contents(
        $areaUserDataPath,
        json_encode($areaUserData)
      );
    }
    
    /**
    * Stores an area user cookie from file
    * @param string $area: area name
    * @param string $areaUserDataKey
    */
    private function getAreaUserData(string $area, string $areaUserDataKey)
    {
      $areaUserDataPath = $this->buildAreaUserDataPath($area, $areaUserDataKey);
      if(is_file($areaUserDataPath)) {
        return json_decode(
          file_get_contents(
            $areaUserDataPath
            )
          );
      } else {
        return new \stdClass;
      }
    }
}
