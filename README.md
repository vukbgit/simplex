## Conventions ##

* __application__: the customized installation of Simplex for the specific project/domain
* files structure:
    * root level application files:
        * composer.json:
            * sets vendor directory to _private/share_
            * sets autoload application directory to _private/local/simplex_ mapping this path to _Simplex\Local_ namespace
            * requires Composer libraries
        * composer.lock
        * README.md
        * .htaccess: redirects ALL requests for the root directory to public/index.php
    * two folders:
        * __private__: all files that CANNOT be accessed by browser
            * __local__: files developed for the application
                * __simplex__: top level namespace folder for application files, every class defined inside has base namespace _Simplex\Local_
            * __share__: files installed through Composer
                * __simplex__: shared Simplex modules used by application
                    * bin: currently specific to my environment, __TODO__: make them useful for others...
                * all the other Composer libraries used by the application
        * __public__: all files that CAN be accessed by browser
            * .htaccess: redirects ALL requests except the ones for files really existing into filesystem (css, js, etc.) for the public directory to public/index.php
            * index.php: application bootstrap file
            * __local__: files developed for the application
            * __share__: files installed through npm, Yarn, etc
                all the npm, Yarn and every other third-part sources libraries used by the application
