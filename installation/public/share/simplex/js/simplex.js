//see application template for global variables set from Twig parameters
/*
* Simplex application level javascript functions
*/
/**********
* COOKIES *
**********/
/**
* Gets the area cookie and return it as an object
* @param string area
* @param string propertyName
**/
getAreaCookie = function(area, propertyName)
{
    areaCookie = Cookies.getJSON(area);
    if(typeof areaCookie === 'undefined') {
        areaCookie = {};
    }
    if(propertyName) {
        return areaCookie['propertyName'];
    }
    return areaCookie;
}

/**
* Sets a property into the area cookie
* @param string area
* @param string propertyName
* @param mixed propertyValue
**/
setAreaCookie = function(area,  propertyName, propertyValue)
{
    areaCookie = getAreaCookie(area);
    areaCookie[propertyName] = propertyValue;
    var path = '/' + area;
    Cookies.set(area, areaCookie, { expires: cookieDurationDays });
}
