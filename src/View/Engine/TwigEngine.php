<?php
namespace Bow\View\Engine;

use Bow\Http\Form;
use Bow\Support\Str;
use Bow\Session\Session;
use Bow\Security\Sanitize;
use Bow\Security\Tokenize;
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
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
        $loader = new \Twig_Loader_Filesystem($config['view.path']);

        $env = [
            'auto_reload' => $config['view.auto_reload_cache'],
            'debug' => true,
            'cache' => $config['view.cache'].'/view'
        ];

        $this->template = new \Twig_Environment($loader, $env);

        /**
         * - Ajout de variable globale
         * dans le cadre de l'utilisation de Twig
         */
        $this->template->addGlobal('_public', $config['app.static']);
        $this->template->addGlobal('_root', $config['app.root']);

        /**
         * - Ajout de fonction global
         *  dans le cadre de l'utilisation de Twig
         */
        $this->template->addFunction(new \Twig_SimpleFunction('secure', 'secure'));
        $this->template->addFunction(new \Twig_SimpleFunction('sanitaze', 'sanitaze'));
        $this->template->addFunction(new \Twig_SimpleFunction('csrf_field', 'csrf_field'));
        $this->template->addFunction(new \Twig_SimpleFunction('csrf_token', 'csrf_token'));
        $this->template->addFunction(new \Twig_SimpleFunction('form', 'form'));
        $this->template->addFunction(new \Twig_SimpleFunction('trans', 'trans'));
        $this->template->addFunction(new \Twig_SimpleFunction('slugify', 'slugify'));
        $this->template->addFunction(new \Twig_SimpleFunction('session', 'session'));

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

    /**
     * @return \Twig_Environment|\Twig_Loader_Filesystem
     */
    public function getTemplate()
    {
        return $this->template;
    }
}