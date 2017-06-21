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
        $this->template->addFunction(new \Twig_SimpleFunction('secure', function($data) {
            return Sanitize::make($data, true);
        }));

        $this->template->addFunction(new \Twig_SimpleFunction('sanitaze', function($data) {
            return Sanitize::make($data);
        }));

        $this->template->addFunction(new \Twig_SimpleFunction('csrf_field', function() {
            return Tokenize::csrf()->field;
        }));

        $this->template->addFunction(new \Twig_SimpleFunction('csrf_token', function() {
            return Tokenize::csrf()->token;
        }));

        $this->template->addFunction(new \Twig_SimpleFunction('form', function() {
            return Form::singleton();
        }));

        $this->template->addFunction(new \Twig_SimpleFunction('trans', function($key, $data = [], $choose = null) {
            return Translator::make($key, $data, $choose);
        }));

        $this->template->addFunction(new \Twig_SimpleFunction('slugify', [Str::class, 'slugify']));

        $this->template->addFunction(new \Twig_SimpleFunction('session', function ($key = null, $value = null){
            if ($key === null && $value === null) {
                return new Session();
            }

            if (Session::has($key)) {
                return Session::get($key);
            }

            if ($key !== null && $value !== null) {
                return Session::add($key, $value);
            }

            return null;
        }));

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