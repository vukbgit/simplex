<?php
declare(strict_types=1);

namespace Simplex;

use Twig\Environment;
use Twig\Loader\LoaderInterface;
//use Twig\Extra\Intl\IntlExtension;
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
        //Twig IntlExtension
        $this->addExtension(new IntlExtension());
        //markdown support
        $markdownEngine = new MarkdownEngine\MichelfMarkdownEngine();
        $this->addExtension(new MarkdownExtension($markdownEngine));
    }
}
