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

* [PHP 7.1+](https://www.php.net/downloads.php)
* ssh access to web space: on a shared hosting it's hard to use Composer (and Yarn and Sass), you have to develop locally and commit, but I really suggest to find a provider who can give you ssh access, once I tried the power & comfort of the ssh shell I rented my own virtual machine and never turned back to shared hosting again...
* even if not strictly required I strongly suggest to have also:
    * [Yarn](https://yarnpkg.com): to install javascript and css libraries
    * [Sass](https://sass-lang.com/) 3.5.2+.: to compile css with variables, mixings and many other useful additions

## Installation ##

Create a Composer.json in the root folder:

    {
        "type": "project",
        "name": "simplex",
        "description": "Simplex dev app",
        "license": "MIT",
        "require": {
            "vukbgit/simplex": "^0.1.0-dev"
        },
        "config": {
            "vendor-dir": "private/share"
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
               "private/share/vukbgit/simplex/drafts/": "."
           }
       }
    }

Create the Composer project running on command line in the root folder:

        Composer create-project

Simplex will:

* install the required Composer libraries (including itself)
* copy in the root directory some files
* make symlinks in the root directory to some shell scripts
* build the filesystem structure for the local application with some ready draft files

For details see _Filesystem structure_ below

## Post-Installation Jobs ##

* __/.htaccess__:
    * set ENVIRONMENT variable
* install __yarn__ packages: preferred location:

    yarn install --modules-folder public/share
* __TODO__


## Simplex Logic overview ##

A bit of terminology:

* __root__: the top folder of the Simplex installation, usually the top folder in the web accessible part of the site web space
* __application__: the customized installation of Simplex for the specific project/domain
* __environment__: in which the current request is handled, based usually on the requested domain, takes usually the values of "development" or "production"
* __action__: the specific logic associated to a route, i.e. 'list' and 'save-form', every route must set an 'action' parameter and it should be formatted as a [slug](https://en.wikipedia.org/wiki/Clean_URL#Slug)

Conventions:

* in the following explanation files are written in _italic_
* for each file is always given the path from the root, without leading slash

This is the flow into the application:

* _.htacces_
    * sets a PHP environment variable base on the domain to decide the current environment
    * intercepts every request and redirects to _index.php_
* _index.php_:
    * requires Composer autoload
    * requires _private/local/simplex/config/constants.php_ that imports some constants (see file for details)
    * set up the __Error Handler__ based on the environment
    * instances a __[Dipendency Injector Container](https://github.com/php-fig/container)__ loading definitions from _private/share/vukbgit/simplex/config/di-container.php_
    * the __DI Container__ instances the __Dispatcher__ (which is another name for a [request handler](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-15-request-handlers.md#21-psrhttpserverrequesthandlerinterface))
    * the dispatcher load the __middleware queue__ from _private/share/vukbgit/simplex/config/middleware.php_ which is basically composed by:
        * the __Router__ which loads routes definitions from any file named "routes.php" stored under the _private/local/simplex_ folder (even in subdirectories); the route definition must contain an "action" parameter (_private/local/simplex/config/route.php_ contains more details about routes definitions)
        * the Simplex __Authentication__ middleware that:
            * fires conditionally if an "authentication" parameter is found inside the current route definition
            * if fired checks whether the ueser is currently authenticated, otherwise redirects to a configured url
        * the __Request Handler__ (no, not the dispatcher, there is a bit of naming confusion), which is responsible for the processing of the current route, invokes the __Route Handler__ (a local class) specified into the route definition which must inherit from one of the Simplex\Controller abstract classes
        * the __Route Handler__:
            * stores all of the request parameters and the response object into class properties
            * calls a method named after the "action" route parameter
            * this method performs all the tasks needed by the action and usually renders a template injecting HTML code into the response
        * the __Dispatcher__ returns the response to the _index.php_ scope
    * the HHTP status code of the response is checked and if different from 200 (which means "everything went fine") gets the appropriate HTML code from a _private/share/vukbgit/simplex/src/errors/_ file and injects it into the response
    * the __Emitter__ is instantiated and returns the response to the browser

* files structure:
    * root level application files:
        * Composer.json:
            * sets vendor directory to _private/share_
            * sets autoload application directory to _private/local/simplex_ mapping this path to _Simplex\Local_ namespace
            * requires Composer libraries
        * Composer.lock
        * README.md
        * .htaccess:
            * sets environment variables that are readable int PHP code
                * based on domain:
                    * ENVIRONMENT: development | production
                * how to read them: Apache renames them prepending 'REDIRECT_' (since every route is redirected to public/index.php), so use for example ``
            * redirects ALL requests for the root directory to public/index.php
        * index.php: application bootstrap file, beeing into site root all PHP includes in every file work with absolute path form site root
    * two folders:
        * __private__: all files that CANNOT be accessed by browser
            * __local__: files developed for the application
                * __simplex__: top level namespace folder for application files, every class defined inside has base namespace _Simplex\Local_
                    * __config__: configuration files for whole application to be customized
                        * __db.php__: database configuration, returns a PHP object (see file for details)
                        * __constants.php__: environment constants
            * __share__: files installed through Composer
                * __simplex__: shared Simplex modules used by application
                    * bin: currently specific to my environment, __TODO__: make them useful for others...
                * all the other Composer libraries used by the application
        * __public__: all files that CAN be accessed by browser
            * .htaccess: redirects ALL requests except the ones for files really existing into filesystem (css, js, etc.) for the public directory to index.php
            * __local__: files developed for the application
            * __share__: files installed through npm, Yarn, etc
                all the npm, Yarn and every other third-part sources libraries used by the application


## General Structure ##

Simplex extends the classes namespace logic to every file in the application;: the __local namespace__ starts from the folder defined into _private/local/simplex/config/constants.php_ LOCAL_DIR constant (defaults to _private/local/simplex_) and is called by default _Simplex\Local_.

Into this folder the classes are arranged as the typical application, by business domain logic (i.e. the _News_ folder for all classes related to news, the _Customer_ folder, etc). But also every other file with different purpose (configuration files, html templates) should follow this logic; so there is no grouping by function first (a top _config_ folder, a top _views_ folder, etc.), but instead by namespace/business logic first (so _/News/config_ and _News/templates_ folders).

This is because tipically application developement proceeds by domain logic: adding the News functionality means adding at least a News class, some News configuration (routes and DI container definitions) and some Nes views (HTML templates for backend and frontend); if all of these files are scattered through local folder subfolders the it's harder to develope,  mantain and "clone" functionalities to be used as draft for new ones

## Add a page ##

Eache page needs a __route__ definition, which calls an __handler__ which is a callable, tipically a class defined into local namesapace, so it needs a DI container definition

* __route__:
* __DI container definitions__:

## Considerations ##

* I choose not to use any framework because I want to be 100% in control of the flow inside the application
* Simplex third party classes for almost every specialized task (DI container, routing, dispatching, emitting...)
* I coded some components into Simplex only when I couldn't find an external library to accomplish some task the way I needed: for example I wrote the nikic/fastroute middleware to be able to pass custom route parameters
* design choices: I tried to document myself, mostly seeking "no framework" suggestions (see references below), and taking a look to existing frameworks (although I am no expert in this field because I started structuring my code for re-use since 2000); I want Simplex to be up-to-date but also to be, well, simple and there is no agreement on every topic, for example [the use of a DI Container](https://hackernoon.com/you-dont-need-a-dependency-injection-container-10a5d4a5f878). Therefore I made my (very questionable) choices, keeping __always__ in mind the I needed a tool to build web applications in the fastest and flexible way
* So I ended up with a framework myself?! Honestly I do not know

## References ##

* [https://github.com/PatrickLouys/no-framework-tutorial]
* [https://kevinsmith.io/modern-php-without-a-framework#properly-sending-responses]
