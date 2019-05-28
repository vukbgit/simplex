<?php
declare(strict_types=1);

namespace Simplex;

use CodeZero\Cookie\VanillaCookie;

/*
* Subclass of  Vanilla Cookie (https://github.com/codezero-be/cookie) to add some functionalities
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
    public function setAreaCookie(string $area, string $propertyName, $propertyValue)
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
    }
    
    /**
    * Gets an area cookie as an object
    * @param string $area: area name
    * @param string $propertyName: optional property yo be returned
    * @return object the whole area cookie
    */
    public function getAreaCookie(string $area, string $propertyName = null)
    {
        $areaCookieJson = $this->get($area);
        $areaCookie = $areaCookieJson ? json_decode($areaCookieJson) : new \stdClass;
        if($propertyName) {
            return $areaCookie->$propertyName ?? null;
        }
        return $areaCookie;
    }
}
