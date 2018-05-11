<?php
namespace Bow\View;

use Bow\Config\Config;
use BadMethodCallException;
use Bow\View\Exception\ViewException;

class View
{
    /**
     * @var Config
     */
    private static $config;

    /**
     * @var View
     */
    private static $instance;

    /**
     * @var EngineAbstract
     */
    private static $template;

    /**
     * @var bool
     */
    private $cachabled = true;

    /**
     * @var array
     */
    private static $container = [
        'twig' => Engine\TwigEngine::class,
        'php' => Engine\PHPEngine::class,
        'mustache' => Engine\MustacheEngine::class,
        'pug' => Engine\PugEngine::class
    ];

    /**
     * View constructor.
     *
     * @param  Config $config
     * @throws ViewException
     */
    public function __construct(Config $config)
    {
        $engine = $config['view.engine'];

        if (is_null($engine)) {
            throw new ViewException('Le moteur de template non défini.', E_USER_ERROR);
        }

        if (!array_key_exists($engine, static::$container)) {
            throw new ViewException('Le moteur de template n\'est pas implementé.', E_USER_ERROR);
        }

        static::$config = $config;
        static::$template = new static::$container[$engine]($config);
    }

    /**
     * Permet de configurer la classe
     *
     * @param Config $config
     */
    public static function configure($config)
    {
        static::$config = $config;
    }

    /**
     * Permet de créer et retourner une instance de View
     *
     * @return View
     */
    public static function getInstance()
    {
        if (!static::$instance instanceof View) {
            static::$instance = new static(static::$config);
        }

        return static::$instance;
    }

    /**
     * Permet de faire le rendu d'une vue
     *
     * @param  string $viewname
     * @param  array  $data
     * @return string
     * @throws ViewException
     */
    public static function make($viewname, array $data = [])
    {
        $data = static::getInstance()->getTemplate()->render($viewname, $data);

        return trim($data);
    }

    /**
     * Permet de récuperer l'instance du template
     *
     * @return EngineAbstract
     */
    public function getTemplate()
    {
        return static::$template;
    }

    /**
     * @param string $engine
     * @return View
     */
    public function setEngine($engine)
    {
        static::$instance = null;
        static::$config['view.engine'] = $engine;

        return $this;
    }

    /**
     * @param $cachabled
     */
    public function cachable($cachabled)
    {
        $this->cachabled = $cachabled;
    }

    /**
     * @param string $extension
     * @return View
     */
    public function setExtension($extension)
    {
        static::$instance = null;
        static::$config['view.extension'] = $extension;

        return $this;
    }

    /**
     * Ajouter un moteur de template
     *
     * @param  $name
     * @param  $engine
     * @return bool
     * @throws ViewException
     */
    public static function pushEngine($name, $engine)
    {
        if (array_key_exists($name, static::$container)) {
            return true;
        }

        if (!class_exists($engine)) {
            throw new ViewException($engine, ' N\'existe pas.');
        }

        static::$container[$name] = $engine;
        return true;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        if (static::$instance instanceof View) {
            if (method_exists(static::$instance, $name)) {
                return call_user_func_array([static::$instance, $name], $arguments);
            }
        }

        throw new BadMethodCallException($name . ' impossible de lance cette methode');
    }

    /**
     * __call
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $method, array $arguments)
    {
        if (method_exists(static::$instance, $method)) {
            return call_user_func_array([static::$instance, $method], $arguments);
        }

        throw new BadMethodCallException("La methode $method n'existe pas");
    }
}
