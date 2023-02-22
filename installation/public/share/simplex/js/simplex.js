//see application template for global variables set from Twig parameters
/*
* Simplex application level javascript functions
*/
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
function EmailUnobsfuscate(atReplacement, dotReplacement) {
    if(!atReplacement || !dotReplacement) {
        alert('for mail obfuscation to be used, MAIL_AT_REPLACEMENT and MAIL_DOT_REPLACEMENT constants must be defined');
    } else {
        // find all links in HTML
        var obfuscatedEmail, email;
        var atRegex = RegExp(atReplacement);
        var dotRegex = RegExp(dotReplacement, 'g');
        var emails = $(".obfuscated-email").each(function(){
            obfuscatedEmail = $(this)
                .attr('href')
                .replace(/mailto:/, '');
            email = obfuscatedEmail
                .replace(atRegex, "@")
                //.replace(/dotReplacement/g, ".")
                .replace(dotRegex, ".")
                ;
            $(this).attr('href', 'mailto:' + email);
            if($(this).text() == obfuscatedEmail) {
                $(this).text(email);
            }
        });
    }
}
//window.onload = EmailUnobsfuscate;
 
 
