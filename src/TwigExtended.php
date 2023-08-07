<?php
declare(strict_types=1);

namespace Simplex;

use Twig\Environment;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\Loader\LoaderInterface;
use Twig\Extra\Intl\IntlExtension;
use jblond\TwigTrans\Translation;
use Aptoma\Twig\Extension\MarkdownExtension;
use Aptoma\Twig\Extension\MarkdownEngine;

/*
* Subclass of Twig tmeplate engine (https://twig.symfony.com) to add some functionalities
*
*/
class TwigExtended extends Environment
{
  /**
   * @var array $filters
   */
  private $filters = [];
  
  /**
   * @var array $functions
   */
  private $functions = [];
  
  /**
    * Constructor.
    * @param Twig\Loader\LoaderInterface $loader
    * @param array $loader: 
    */
  public function __construct(LoaderInterface $loader, array $options = [])
  {
    parent::__construct($loader, $options);
    //internationalization
    //$this->addExtension(new \Twig_Extensions_Extension_I18n());
    $this->addExtension(new Translation());
    $filter = new TwigFilter(
        'trans', 
        /*function ($context, $string) {
            return Translation::transGetText($string, $context);
        },*/
        'gettext',
        [
          //'needs_context' => true,
          'is_safe_callback' => 'twig_escape_filter_is_safe',
        ]
    );
    $this->addFilter($filter);
    //Twig IntlExtension
    $this->addExtension(new IntlExtension());
    //markdown support
    $markdownEngine = new MarkdownEngine\MichelfMarkdownEngine();
    $this->addExtension(new MarkdownExtension($markdownEngine));
    //set context variable
    $this->addExtension(new class extends \Twig\Extension\AbstractExtension {
      public function getFunctions() {
        return [
          new \Twig\TwigFunction('setContextVar', [$this, 'setContextVar'], ['needs_context' => true]),
        ];
      }
      public function setContextVar(&$context, $name, $value) {
        $context[$name] = $value;
      }
    });
  }
  
  /**
    * Adds a filter
    * @param TwigFilter $filter
    */
  public function addFilter(TwigFilter $filter)
  {
    if(!in_array($filter->getName(), $this->filters)) {
      $this->filters[] = $filter->getName();
      parent::addFilter($filter);
    }
      
  }
  
  /**
    * Adds a function
    * @param TwigFunction $function
    */
  public function addFunction(TwigFunction $function)
  {
    if(!in_array($function->getName(), $this->functions)) {
      $this->functions[] = $function->getName();
      parent::addFunction($function);
    }
      
  }
}
