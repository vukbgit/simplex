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
/********
* EMAIL *
********/
/**
 * converts an email written in obfuscated style into html back to proper syntax
 * Email must be put into html code with this syntax:
 * @ -> (at)
 * . -> dot
 * spaces are trimmed
 */
function EmailUnobsfuscate() {
    // find all links in HTML
    var link = document.getElementsByTagName && document.getElementsByTagName("a");
    var email, e;
    
    // examine all links
    for (e = 0; link && e < link.length; e++) {
        
        // does the link have use a class named "email"
        if ((" "+link[e].className+" ").indexOf(" obfuscated-email ") >= 0) {
            
            // get the obfuscated email address
            email = link[e].firstChild.nodeValue.toLowerCase() || "";
            
            // transform into real email address
            email = email.replace(/dot/ig, ".");
            email = email.replace(/\(at\)/ig, "@");
            email = email.replace(/\s/g, "");
            
            // is email valid?
            if (/^[^@]+@[a-z0-9]+([_.-]{0,1}[a-z0-9]+)*([.]{1}[a-z0-9]+)+$/.test(email)) {
                // change into a real mailto link
                link[e].href = "mailto:" + email;
                link[e].firstChild.nodeValue = email;
                
            }
        }
    }
}
window.onload = EmailUnobsfuscate;
