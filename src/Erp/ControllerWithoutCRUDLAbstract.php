<?php
declare(strict_types=1);

namespace Simplex\Erp;

use Simplex\Controller\ControllerWithTemplateAbstract;

use Psr\Http\Message\ServerRequestInterface;

/*
* Extends the ControllerAbstract class adding CRUDL (https://en.wikipedia.org/wiki/Create,_read,_update_and_delete) funcionalities:
* - handling of list, create-form, update-form, delete-form, create, update and delete actions
* - requires route to pass a 'model' parameter, a class with the same name will be looked for in current namespace
*/
abstract class ControllerWithoutCRUDLAbstract extends ControllerWithTemplateAbstract
{
    /**
    * Performs some operations before action execution
    * @param ServerRequestInterface $request
    */
    protected function doBeforeActionExecution(ServerRequestInterface $request)
    {
        //Parent jobs
        parent::doBeforeActionExecution($request);
        //build common template helpers
        $this->buildCommonTemplateHelpers();
        //load navigation
        if($this->isAuthenticated()) {
            //area navigation which is *always* needed for ERP 
            $this->loadAreaNavigation();
        }
    }
    
    /**
    * Build common template helpers
    */
    protected function buildCommonTemplateHelpers()
    {
        /*************
        * NAVIGATION *
        *************/
        //gets a local controller navigations object
        $this->addTemplateFunction('getNavigations', function(ControllerWithTemplateAbstract $controller){
            $controller->loadSubjectNavigation();
            return $controller->getNavigations();
        });
        //parses a record action route pattern replacing placeholders with record values
        $this->addTemplateFunction(
            'parseRecordActionRoute',
            function(string $routePattern, object $record){
                //get route pattern placeholders
                preg_match_all('/\{([a-z0-9_]+)\}/', $routePattern, $placeholders);
                $placeholders = $placeholders[1];
                //loop placeholders to find replacements
                $replacements = [];
                foreach ($placeholders as $placeholderIndex => $placeholder) {
                    $placeholders[$placeholderIndex] = sprintf('/{(%s)}/', $placeholder);
                    //placeholder value found
                    if(isset($record->$placeholder)) {
                        $replacements[$placeholderIndex] = $record->$placeholder;
                        continue;
                    }
                    //default placeholder value is null
                    $replacements[$placeholderIndex] = null;
                }
                $route = preg_replace($placeholders, $replacements, $routePattern);
                return $route;
            }
        );
        /*********
        * LABELS *
        *********/
        //builds an ancestor label
        $this->addTemplateFunction(
            'buildAncestorRecordLabel',
            function(string $subjectKey): string{
                $ancestor = $this->ancestors[$subjectKey];
                $CRUDLConfig = $ancestor->controller->getCRUDLConfig();
                $label = '';
                if(isset($CRUDLConfig->labelTokens)) {
                    $labelTokens = [];
                    foreach ((array) $CRUDLConfig->labelTokens as $token) {
                        $labelTokens[] = isset($ancestor->record->$token) ? (is_array($ancestor->record->$token) ? $ancestor->record->$token[$this->language->{'ISO-639-1'}] : $ancestor->record->$token) : $token;
                    }
                    $label = implode('', $labelTokens);
                }
                return $label;
            }
        );
        /*********
        * ALERTS *
        *********/
        //resets subject alerts
        $this->addTemplateFunction(
            'resetSubjectAlerts',
            function(){
                return $this->resetSubjectAlerts();
            }
        );
    }
}
