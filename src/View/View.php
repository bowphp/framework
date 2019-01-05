<?php

namespace Bow\View;

use BadMethodCallException;
use Bow\Configuration\Loader;
use Bow\Contracts\ResponseInterface;
use Bow\View\Exception\ViewException;

class View implements ResponseInterface
{
    /**
     * @var Loader
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
     * @var string
     */
    private static $content;

    /**
     * @var bool
     */
    private $cachabled = true;

    /**
     * @var array
     */
    private static $engines = [
        'twig' => \Bow\View\Engine\TwigEngine::class,
        'php' => \Bow\View\Engine\PHPEngine::class,
        'tintin' => \Tintin\Bow\TintinEngine::class
    ];

    /**
     * View constructor.
     *
     * @param  Loader $config
     * @throws ViewException
     */
    public function __construct(Loader $config)
    {
        $engine = $config['view.engine'];

        if (is_null($engine)) {
            throw new ViewException('Le moteur de template non défini.', E_USER_ERROR);
        }

        if (!array_key_exists($engine, static::$engines)) {
            throw new ViewException('Le moteur de template n\'est pas implementé.', E_USER_ERROR);
        }

        static::$config = $config;

        static::$template = new static::$engines[$engine]($config);
    }

    /**
     * Permet de configurer la classe
     *
     * @param Loader $config
     */
    public static function configure($config)
    {
        static::$config = $config;
    }

    /**
     * Permet de créer et retourner une instance de View
     *
     * @return View
     * @throws
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
     * @return View
     */
    public static function parse($viewname, array $data = [])
    {
        static::$content = static::getInstance()->getTemplate()->render($viewname, $data);

        return static::$instance;
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
     * Set Engine
     *
     * @param string $engine
     * @return View
     */
    public function setEngine($engine)
    {
        static::$instance = null;

        static::$config['view.engine'] = $engine;

        return static::getInstance();
    }

    /**
     * Set the availability of caching system
     *
     * @param bool $cachabled
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

        return static::getInstance();
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
        if (array_key_exists($name, static::$engines)) {
            return true;
        }

        if (!class_exists($engine)) {
            throw new ViewException($engine . ' n\'existe pas.');
        }

        static::$engines[$name] = $engine;

        return true;
    }

    /**
     * Get rendering content
     *
     * @return string
     */
    public function getContent()
    {
        return static::$content;
    }

    /**
     * Send Response
     *
     * @return mixed
     */
    public function sendContent()
    {
        echo static::$content;

        return;
    }

    /**
     * __toString
     *
     * @return string
     */
    public function __toString()
    {
        return static::$content;
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
