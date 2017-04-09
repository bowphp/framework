<?php
namespace Bow\View\Engine;

use Bow\Support\Str;
use Bow\Security\Security;
use Bow\View\EngineAbstract;
use Bow\Translate\Translator;
use Bow\Application\Configuration;

class TwigEngine extends EngineAbstract
{
    /**
     * @var \Twig_Loader_Filesystem
     */
    private $template;

    /**
     * @var string
     */
    protected $name = 'twig';

    /**
     * TwigEngine constructor.
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
        $loader = new \Twig_Loader_Filesystem($config->getViewpath());
        $this->template = new \Twig_Environment($loader, [
            'cache' => $config->getCachepath(),
            'auto_reload' => $config->getCacheAutoReload(),
            'debug' => $config->getLoggerMode() == 'develepment' ? true : false
        ]);

        /**
         * - Ajout de variable globale
         * dans le cadre de l'utilisation de Twig
         */
        $this->template->addGlobal('public', $config->getPublicPath());
        $this->template->addGlobal('root', $config->getApproot());

        /**
         * - Ajout de fonction global
         *  dans le cadre de l'utilisation de Twig
         */
        $this->template->addFunction(new \Twig_SimpleFunction('secure', function($data) {
            return Security::sanitaze($data, true);
        }));
        $this->template->addFunction(new \Twig_SimpleFunction('sanitaze', function($data) {
            return Security::sanitaze($data);
        }));
        $this->template->addFunction(new \Twig_SimpleFunction('csrf_field', function() {
            return Security::getCsrfToken()->field;
        }));
        $this->template->addFunction(new \Twig_SimpleFunction('csrf_token', function() {
            return Security::getCsrfToken()->token;
        }));
        $this->template->addFunction(new \Twig_SimpleFunction('trans', function($key, $data = [], $choose = null) {
            return Translator::make($key, $data, $choose);
        }));
        $this->template->addFunction(new \Twig_SimpleFunction('slugify', [Str::class, 'slugify']));
        return $this->template;
    }

    /**
     * @inheritDoc
     */
    public function render($filename, array $data = [])
    {
        $filename = $this->checkParseFile($filename);
        return $this->template->render($filename, $data);
    }
}