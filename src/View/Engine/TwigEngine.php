<?php
namespace Bow\View\Engine;

use Bow\View\EngineAbstract;
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
        $this->template->addFunction(new \Twig_SimpleFunction('route', 'route'));
        $this->template->addFunction(new \Twig_SimpleFunction('bow_hash', 'bow_hash'));
        $this->template->addFunction(new \Twig_SimpleFunction('config', 'config'));
        $this->template->addFunction(new \Twig_SimpleFunction('faker', 'faker'));
        $this->template->addFunction(new \Twig_SimpleFunction('env', 'env'));
        $this->template->addFunction(new \Twig_SimpleFunction('app_mode', 'app_mode'));
        $this->template->addFunction(new \Twig_SimpleFunction('app_lang', 'app_lang'));
        $this->template->addFunction(new \Twig_SimpleFunction('flash', 'flash'));
        $this->template->addFunction(new \Twig_SimpleFunction('cache', 'cache'));
        $this->template->addFunction(new \Twig_SimpleFunction('encrypt', 'encrypt'));
        $this->template->addFunction(new \Twig_SimpleFunction('decrypt', 'decrypt'));
        $this->template->addFunction(new \Twig_SimpleFunction('collect', 'collect'));
        $this->template->addFunction(new \Twig_SimpleFunction('url', 'url'));
        $this->template->addFunction(new \Twig_SimpleFunction('get_header', 'get_header'));
        $this->template->addFunction(new \Twig_SimpleFunction('input', 'input'));

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