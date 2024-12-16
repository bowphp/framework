<?php

declare(strict_types=1);

namespace Bow\View;

use Tintin\Tintin;
use BadMethodCallException;
use Bow\View\EngineAbstract;
use Bow\Contracts\ResponseInterface;
use Bow\View\Exception\ViewException;

class View implements ResponseInterface
{
    /**
     * The application loader
     *
     * @var array
     */
    private static array $config;

    /**
     * The View singleton instance
     *
     * @var View
     */
    private static ?View $instance = null;

    /**
     * The template Engine extension
     *
     * @var EngineAbstract
     */
    private static EngineAbstract $template;

    /**
     * The view rendering content
     *
     * @var string
     */
    private static string $content;

    /**
     * The build-in template engine
     *
     * @var array
     */
    private static array $engines = [
        'tintin' => \Tintin\Bow\TintinEngine::class,
        'twig' => \Bow\View\Engine\TwigEngine::class,
        'php' => \Bow\View\Engine\PHPEngine::class,
    ];

    /**
     * The cachabled flash for twig
     *
     * @var boolean
     */
    private bool $cachabled = false;

    /**
     * View constructor.
     *
     * @param  array $config
     * @return  void
     * @throws ViewException
     */
    public function __construct(array $config)
    {
        $engine = $config['engine'] ?? null;

        if (is_null($engine)) {
            throw new ViewException(
                'The view engine is not define.',
                E_USER_ERROR
            );
        }

        if (!array_key_exists($engine, static::$engines)) {
            throw new ViewException(
                'The view engine is not implemented.',
                E_USER_ERROR
            );
        }

        static::$config = $config;

        static::$template = new static::$engines[$engine]($config);
    }

    /**
     * Load view configuration
     *
     * @param array $config
     * @return void
     */
    public static function configure(array $config): void
    {
        static::$config = $config;
    }

    /**
     * Get the view singleton instance
     *
     * @return View
     * @throws
     */
    public static function getInstance(): View
    {
        if (!static::$instance instanceof View) {
            static::$instance = new View(static::$config);
        }

        return static::$instance;
    }

    /**
     * Parse the view
     *
     * @param  string $viewname
     * @param  array  $data
     * @return View
     */
    public static function parse(string $view, array $data = []): View
    {
        static::$content = static::getInstance()
            ->getTemplate()
            ->render($view, $data);

        return static::$instance;
    }

    /**
     * Get the template engine instance
     *
     * @return EngineAbstract
     */
    public function getTemplate()
    {
        return static::$template;
    }

    /**
     * Get the engine
     *
     * @return Tintin|\Twig\Environment
     */
    public function getEngine()
    {
        return static::$template->getEngine();
    }

    /**
     * Set Engine
     *
     * @param string $engine
     * @return View
     */
    public function setEngine(string $engine): View
    {
        static::$instance = null;

        static::$config['engine'] = $engine;

        return static::getInstance();
    }

    /**
     * Set the availability of caching system
     *
     * @param bool $cachabled
     * @return void
     */
    public function cachable(bool $cachabled): void
    {
        $this->cachabled = $cachabled;
    }

    /**
     * @param string $extension
     * @return View
     */
    public function setExtension(string $extension): View
    {
        static::$instance = null;

        static::$config['extension'] = $extension;

        return static::getInstance();
    }

    /**
     * Ajouter un moteur de template
     *
     * @param  $name
     * @param  $engine
     *
     * @return bool
     * @throws ViewException
     */
    public static function pushEngine(string $name, string $engine): bool
    {
        if (array_key_exists($name, static::$engines)) {
            return true;
        }

        if (!class_exists($engine)) {
            throw new ViewException(
                sprintf('%s does not exists.', $engine)
            );
        }

        static::$engines[$name] = $engine;

        return true;
    }

    /**
     * Get rendering content
     *
     * @return string
     */
    public function getContent(): string
    {
        return static::$content;
    }

    /**
     * Send Response
     *
     * @return mixed
     */
    public function sendContent(): void
    {
        echo static::$content;

        return;
    }

    /**
     * Check if the define file exists
     *
     * @param string $filename
     * @return bool
     */
    public function fileExists(string $filename): bool
    {
        return static::$template->fileExists($filename);
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
     * __callStatic
     *
     * @param string $name
     * @param array $arguments
     *
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        if (static::$instance instanceof View) {
            if (method_exists(static::$instance, $name)) {
                return call_user_func_array(
                    [static::$instance, $name],
                    $arguments
                );
            }
        }

        throw new BadMethodCallException(
            sprintf('%s method does not exists.', $name)
        );
    }

    /**
     * __call
     *
     * @param string $method
     * @param array $arguments
     *
     * @return mixed
     */
    public function __call(string $method, array $arguments)
    {
        if (method_exists(static::$instance, $method)) {
            return call_user_func_array(
                [static::$instance, $method],
                $arguments
            );
        }

        throw new BadMethodCallException(
            "The method $method does not exists"
        );
    }
}
