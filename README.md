# Simplex #

## Introduction ##

Simplex is a tool for developing PHP/HTML/CSS web applications. In short, it sets up a handy environment so you hopefully only have to code files specific to the project business logic: PHP (classes and some configuration file), HTML ([Twig](https://twig.symfony.com/doc/2.x/) templates) and CSS or [SCSS](https://sass-lang.com/).

The goal of Simplex is to provide:

* the structure for a PHP web application that is a compromise between:
    * simplicity
    * the latest standards and practices (as far as I know)
* a quick and easy way to get started developing code for the application

To do so Simplex relies on:
* [Composer](https://getComposer.org) packages (by means of [Packagist](https://packagist.org/) packages):
    * Simplex itself is a Composer package that:
        * takes care of installing the required libraries (for at least the minimum functionalities)
        * creates the basic starting structure for the application with some draft files almost ready to be used but that can be deleted, modified and integrated at need
    * other selected Composer packages are integrated to create the application core engine
* [Yarn](https://yarnpkg.com) for all the [NPM](https://npmjs.com) packages:
    * [bootstrap 4](https://getbootstrap.com)
    * [jquery](http://jquery.com/)

_NOTE ON THIS DOCUMENT_: I will try to be clear and write down all the details to understand and use Simplex, for future-me, any possible colleague and anyone else interested benefit

## Requirements ##

* [Apache 2.4+ webserver](http://httpd.apache.org/): althoungh not strictly necessary for the core engine, .htaccess files are used for basic routing and domain based environment detection
* [PHP 7.1+](https://www.php.net/downloads.php) with the PHP [gettext](http://www.php.net/gettext) extension enabled (beacuse every piece of text is considered as a translation even in a mono language application)
* ssh access to web space: on a shared hosting it's hard to use Composer (and Yarn and Sass), you have to develop locally and commit, but I really suggest to find a provider who can give you ssh access, once I tried the power & comfort of the ssh shell I rented my own virtual machine and never turned back to shared hosting again...
* even if not strictly required I strongly suggest to have also:
    * [Yarn](https://yarnpkg.com): to install javascript and css libraries
    * [Sass](https://sass-lang.com/) 3.5.2+.: to compile css with variables, mixings and many other useful additions

## Installation ##

Create a Composer.json in the root folder:

    {
        "type": "project",
        "name": "simplex",
        "description": "Simplex app",
        "license": "MIT",
        "require": {
            "vukbgit/simplex": "^0.1.0-dev"
        },
        "config": {
            "vendor-dir": "private/share",
            "bin-dir": "./"
        },
        "autoload": {
            "psr-4": {
                "Simplex\\Local\\": "private/local/simplex"
            }
        },
        "scripts": {
           "post-create-project-cmd": [
               "SlowProg\\CopyFile\\ScriptHandler::copy"
           ]
       },
       "extra": {
           "copy-file": {
               "private/share/vukbgit/simplex/installation/": "."
           }
       }
    }

Create the Composer project running on command line in the root folder:

        composer create-project

Simplex will:

* install itself and the other required Composer libraries
* copy in the root directory some files 
* make symlinks in the root directory to some shell scripts ([Composer vendor binaries](https://getcomposer.org/doc/articles/vendor-binaries.md))
* build the folders structure for the local application with some ready draft files

For details see _Folders & Files structure_ below

## Post-Installation Jobs ##

* __/.htaccess__:
    * set ENVIRONMENT variable:
        * using `SetEnvIf` directive you can set an enviromental varible name _ENVIRONMENT_ which can be read inside PHP scripts as a constant with `getenv('REDIRECT_ENVIRONMENT')`
        * value is based on requested domain and the format is:
            
            SetEnvIf Host ^domain\.ltd$ ENVIRONMENT=value
            
        * _domain\.ltd_ must be replaced by a valid domain name (beware the escaped dot)
        * _value_ can be either _development_ or _production_ and Simplex expects to have at least one domain mapped to _production_
* local __composer__ bash script: if your system has multiple PHP versions installed it can be useful to have a shortcut to use composer with a version different from the system default one; file _private\local\simplex\bin\composer.sh_ can be set with the path to the right PHP binary and softlinked into root folder
* install __yarn__ packages:
    * the _public\share\package.json_ file contains some NPM packages for:
        * backends or enterprise web applications (ERP):
            * [PatternFly 4](https://pf4.patternfly.org): a "an open source design system created to enable consistency and usability across a wide range of applications and use cases", Simplex uses it for ERP and backend building in the provided drafts
            * [parsleyjs](http://parsleyjs.org/): form validation
            * [moment](https://momentjs.com/) for dates and time manipulation
            * [tempus dominus](https://tempusdominus.github.io/bootstrap-4/) for date/time pickers
        * frontend
            * [bootstrap 4](https://getbootstrap.com)
            * [jquery](http://jquery.com/)
            * [jquery.safemail](https://github.com/leftclickcomau/jquery.safemail) for obfuscating email
    * edit the file if needed to include/exclude packages
    * install packages: you can use the _yarn.sh_ symlink from the root folder:
        
            ./yarn.sh install
        
    * packages are installed under \public\share\node_modules_
* local __configuration__: file _private\local\simplex\config\constants.php_ defines some PHP constants, most of them regard paths and it should not be necessary to modify them unless you plan to change the filesystem structure Simplex relies on; informations to be set for the local application (tech email, application name...) are grouped into the _LOCAL_ block at the top of the script
* __database__ configuration: in case application connects to a database edit file _private\local\simplex\config\db.php_; database accounts are organized by _ENVIRONMENT_ constant values (see __.htacces__ above), so you can have different accounts for development and production
* __languages__: file _private\local\simplex\config\langauges.json_ defines used languages, contains by default definitions for English and Italian, add as many as required; language selection works this way:
    * if current route contains a 'lang' parameter (tipically a part of the route itself for multilanguage sites) that language is used
    * otherwise the first language defined into _private\local\simplex\config\langauges.json_ is used, so if you only need one language you can put its definition in first position
* set up application _template__: a Twig template to be used for the whole application is provided in _private/local/templates/application.twig_:
    * it includes a Twig macro to display favicon generated by https://www.favicon-generator.org, if you want to use it go to website, generate icons images, upload them (i.e. to _public/local/corporate/favicon_) and set path as argument to the favicon() macro
    * remove the <h1> tag

## Backend / ERP Development TODO ##

Simplex include an ERP namespace based on [PatternFly 4](https://pf4.patternfly.org) and uses it to build backends
* login form route
* methods:
    * htpasswd: create password file for example in _private/local/simplex/Backend/config/.htpasswd_

        htpasswd -cB -C10 .htpasswd your-username
    
* patternfly compilation

## Simplex Logic overview TODO ##

A bit of terminology:

* __root__: the top folder of the Simplex installation, usually the top folder in the web accessible part of the site web space
* __application__: the customized installation of Simplex for the specific project/domain
* __environment__: in which the current request is handled, based usually on the requested domain, takes usually the values of "development" or "production"
* __action__: the specific logic associated to a route, i.e. 'list' and 'save-form', every route must set an 'action' parameter and it should be formatted as a [slug](https://en.wikipedia.org/wiki/Clean_URL#Slug)

Conventions:

* in the following explanation files are written in _italic_
* for each file is always given the path from the root, without leading slash

### Application Flow ###

* _.htacces_
    * sets a PHP environment variable base on the domain to decide the current environment
    * intercepts every request and redirects to _index.php_
* _index.php_:
    * requires Composer autoload
    * searches for files named _constants.php_ under folder _private/local/simplex_, during installation _private/local/simplex/config/constants.php_ is created (see file for details)
    * set up the __Error Handler__ based on the environment
    * instances a __[Dipendency Injector Container](https://github.com/php-fig/container)__ loading definitions from _private/share/vukbgit/simplex/config/di-container.php_ (see file for details)
    * the __DI Container__ instances the __Dispatcher__ (which is another name for a [request handler](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-15-request-handlers.md#21-psrhttpserverrequesthandlerinterface))
    * the dispatcher loads the __middleware queue__ from the MIDDLEWARE_QUEUE_PATH constant value (defaults to _private/share/vukbgit/simplex/config/middleware.php_), Simplex default queue is composed by:
        * the __Router__ which loads routes definitions from any file named "routes.php" stored under the _private/local/simplex_ folder (even in subdirectories); the route definition must contain an "action" parameter (_private/local/simplex/config/route.php_ contains more details about routes definitions)
        * the Simplex __Authentication__ middleware that:
            * fires conditionally if an "authentication" parameter is found inside the current route definition
            * if fired checks whether the user is currently authenticated, otherwise redirects to a configured url
        * the __Request Handler__ (no, not the __Dispatcher__ from above, there is a bit of naming confusion on this matter...), which is responsible for processing the current route, invokes the __Route Handler__ (a local class) specified into the route definition which must inherit from one of the Simplex\Controller abstract classes
        * the __Route Handler__:
            * stores all of the request parameters and the response object into class properties
            * calls a method named after the "action" route parameter
            * this method performs all the tasks needed by the action and usually renders a template injecting HTML code into the response
        * the __Dispatcher__ returns the response to the _index.php_ scope
    * the HTTP status code of the response is checked and if different from 200 (which means "everything went fine") gets the appropriate HTML code from a _private/share/vukbgit/simplex/src/errors/_ file and injects it into the response
    * the __Emitter__ is instantiated and returns the response to the browser

## Folders & Files Structure ##

Simplex extends the classes namespace logic to every file in the application;: the __local namespace__ starts from the folder defined into _private/local/simplex/config/constants.php_ LOCAL_DIR constant (defaults to _private/local/simplex_) and is named by default _Simplex\Local_.

Into this folder the classes are arranged as the typical application, by business domain logic (i.e. the _News_ folder for all classes related to news, the _Customer_ folder, etc). But also every other file with different purpose (configuration files, html templates, SASS files...) should follow this logic; so there is no grouping by function first (a top _config_ folder, a top _views_ folder, etc.), but instead by namespace/business logic first (so _/News/config_ and _News/templates_ folders).

This is because typically application development proceeds by domain logic: adding the News functionality means adding at least a News class, some News configuration (routes and DI container definitions) and some News views (HTML templates for backend and frontend); if all of these files are scattered through local folder subfolders I find it harder to develope,  mantain and "clone" functionalities to be used as draft for new ones

So here are folders and files as installed from Simplex, from the installation root folder:

* __private__: all files that CANNOT be directly accessed by browser
    * __local__: files developed for the application
        * __simplex__: top level namespace folder for application files, every class defined inside has base namespace _Simplex\Local_
            * __Backend__: frontend namespace draft folder
            * __Frontend__: frontend namespace draft folder
            * __bin__: created at installation time for useful bash scripts
                * __composer.sh__: allows to use composer with a PHP version different from the system default one used by the PHP CLI application, useful on a system with multiple PHP versions installed; it's a good idea to soft link it into root
            * __config__: configuration files for whole application to be customized
                * __constants.php__: environment constants, quite self explanatory, some of them should be set right after installation; NOTE: most of the regards paths Simplex uses for inclusions, it shouldn't be necessary to change them; if so beware that a pair of paths are hard coded into _index.php_ prior to including this file and should be changed manually
                * __db.php__: database configuration, returns a PHP object, to be compiled if application uses a database (see file for details)
                * __di-container.php__: definition to be used by the DI Container to instantiate the classes used by the application; it integrates __private/local/share/vukbgit/simplex/src/config/di-container.php/__ which stores the definitions for classes used by the Simplex engine
                * __languages.json__: languages used by the application, indexed by a custom key (the one proposed is the ISO-639-1 two letters code); if the route passes a "language" parameter, language is searched for otherwise first one defined (defaults to English) it's used
                * __sass.config__: custom format file to speed up Sass files compilation using the _sass.sh_ script: you can define for each file to be compiled a custom id (any string) and source and destination paths, so you you ca use the shell and call from the root folder `sass file-id` to compile the minified CSS version
            * __sass__: some scss empty drafts to help compile Bootstrap and some application css
                * __application.scss__: rules for the whole application
                * __bootstrap-variables.scss__: it is included BEFORE the file with the _variables.scss_ shipped with Bootstrap to override Bootstrap built-in variables
                * __bootstrap.scss__: main file to compile Bootstrap css, includes only the most commonly used components, uncomment lines to include other functionalities; _private/local/simplex/config/sass.config_ already contains configuration to compile this file by means of the root _sass.sh_ file, just executing in the shell  './sass.sh bs'
                * __functions.scss__: definitions for some useful Sass functions
                * __variables.scss__: Sass variables to be used by the application
            * __templates__: some ready to use and customize Twig templates
    * __share__: files installed through Composer and possibly other third-part libraries from other sources
        * __vukbgit__
            * __simplex__: shared Simplex modules used by application, some explanations about the less obvious ones:
                * __bin__: bash scripts, some of the soft linked into root at installation composer project creation time
                * __installation__: folders and files copied at installation time ready to be used and/or to be customized
                * __src__: classes and other files used by Simplex at runtime
                    * __config__: configuration files
                        * __di-container.php__: definition to be used by the DI Container to instantiate the classes used by the Simplex engine; it is integrated by ANY file with the same name found under the __private/local/simplex__ folder
                        * __middleware.php__: middleware queue to be processed by the Dispatcher, can be overridden setting _MIDDLEWARE_QUEUE_PATH_ value into _private\local\simplex\config\constants.php_
                    * __errors__: HTML files to be displayed in case of HTTP errors raised by the request
                    * __templates__: ready to use Twig templates for backend areas with CRUDL functionalities
        * all the other Composer libraries used by the application
* __public__: all files that CAN be accessed by browser
    * __local__: files developed for the application such as compiled css files and javascript files
    * __share__: libraries installed through npm, Yarn, and any other third-part javascript and css asset
        * __package.json__: npm/Yarn configuration file, requires Bootstrap, jQuery and popper.js lates plus [patternfly 4](https://pf4.patternfly.org), customize at need
    * __.htaccess__: redirects ALL requests beginning with "public/" to _index.php_ except the ones for files really existing into filesystem (css, js, etc.)
* __.gitignore__: in a development/production flow I commit file to a private repository form the development site and pull them into the production one; this .gitignore file excludes some Simplex folders/files from commit
* __.htaccess__: root Apache directives
    * sets environment variables that are readable into PHP code
        * based on domain:
            * ENVIRONMENT: development | production
        * how to read them: Apache renames them prepending 'REDIRECT_' (since every route is redirected to public/index.php), so use for example `getenv('REDIRECT_ENVIRONMENT')`
    * redirects ALL requests for the root directory to public/index.php
* __composer.json__:
    * sets vendor directory to _private/share_
    * sets bin directory to _./_ so that symlinks are created into root for some shell scripts
    * sets autoload application directory to _private/local/simplex_ mapping this path to _Simplex\Local_ namespace
    * requires the Simplex package (which takes care of requiring the other needed packages)
* __index.php__: application bootstrap file, since it is stored into site root all PHP includes in every file work with absolute path form site root, see "Application Flow" above for details
* __sass.sh__: soft link to the helper script _private/share/vukbgit/simplex/bin/sass.sh_ to compile Sass files, see the _private/local/simplex/config/sass.config_ explanation above for details
* __yarn.sh__: soft link to the helper script _private/share/vukbgit/simplex/bin/yarn.sh_ to manage yarn packages into _public/share_ folder (instead of the predefined node_modules one), call it `./yarn.sh yarn-command`, i.e `./yarn.sh install foolibrary` to perform the installation into _local/share/foolibrary_

## Considerations ##

* I choose not to use any framework because I want to be 100% in control of the flow inside the application
* Simplex uses third party classes for almost every specialized task (DI container, routing, dispatching, emitting...)
* I coded some components into Simplex only when I couldn't find an external library to accomplish some task the way I needed: for example I wrote the nikic/fastroute middleware to be able to pass custom route parameters
* design choices: I tried to search documentation, mostly seeking "no framework" suggestions (see references below), and taking a look to existing frameworks (although I am no expert in this field because I started structuring my code for re-use since 2000); I want Simplex to be up-to-date but also to be, well, simple and there is no agreement on every topic, for example [the use of a DI Container](https://hackernoon.com/you-dont-need-a-dependency-injection-container-10a5d4a5f878). Therefore I made my (very questionable) choices, keeping __always__ in mind the I needed a tool to build web applications in the fastest and most flexible way
* So I ended up with a framework myself?! Honestly I do not know

## References ##

* [https://github.com/PatrickLouys/no-framework-tutorial]
* [https://kevinsmith.io/modern-php-without-a-framework#properly-sending-responses]
