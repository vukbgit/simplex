# Simplex #

A tool for web developers

### Table of Contents ###

* [Introduction](#Introduction)
* [Requirements](#Requirements)
* [Terminology](#Terminology)
* [Installation](#Installation)
* [Post-Installation Jobs](#Post-Installation-Jobs)
* [Area set up](#Area-set-up)
    * [Backend / ERP](#Backend--ERP)
* [Subject set up](#Subject-set-up)
* [Debugging](#Debugging)
* [Internazionalization](#Internazionalization)
* [Icon Fonts](#Icon-Fonts)
* [Development to Production](#Development-to-Production)
* [Simplex Logic overview](#Simplex-Logic-overview)
    * [Logical Structure](#Logical-Structure)
    * [Application Flow](#Application-Flow)
* [Folders and Files Structure](#Folders-and-Files-Structure)
* [Considerations](#Considerations)
* [API Documentation](#API-Documentation)
* [References](#References)

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
* [Fontello](http://fontello.com/) for icons

_NOTE ON THIS DOCUMENT_: I will try to be clear and write down all the details to understand and use Simplex, for future-me, any possible colleague and anyone else interested benefit

## Requirements ##

* [Apache 2.4+ webserver](http://httpd.apache.org/): althoungh not strictly necessary for the core engine, .htaccess files are used for basic routing and domain based environment detection
* [PHP 7.1+](https://www.php.net/downloads.php) with the PHP [gettext](http://www.php.net/gettext) extension enabled (beacuse every piece of text is considered as a translation even in a mono language application)
* ssh access to web space: on a shared hosting it's hard to use Composer (and Yarn and Sass), you have to develop locally and commit, but I really 