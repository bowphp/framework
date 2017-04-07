<?php
namespace Bow\View;

use Bow\Application\Configuration;
use Bow\Exception\ViewException;

class View
{
    /**
     * @var Configuration
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
     * @var array
     */
    private static $container = [
        'twig' => \Bow\View\Engine\TwigEngine::class,
        'php' => \Bow\View\Engine\PHPEngine::class,
        'mustache' => \Bow\View\Engine\MustacheEngine::class,
        'pug' => \Bow\View\Engine\PugEngine::class
    ];

    /**
     * View constructor.
     * @param Configuration $config
     * @throws ViewException
     */
    public function __construct(Configuration $config)
    {
        if (static::$config->getTemplateEngine() === null) {
            throw new ViewException('Le moteur de template non défini.', E_USER_ERROR);
        }

        if (! in_array(static::$config->getTemplateEngine(), ['twig', 'mustache', 'pug', 'php'], true)) {
            throw new ViewException('Le moteur de template n\'est pas implementé.', E_USER_ERROR);
        }

        static::$config = $config;
        static::$template = new static::$container[static::$config->getTemplateEngine()]($config);
    }

    /**
     * Permet de configurer la classe
     *
     * @param Configuration $config
     */
    public static function configure(Configuration $config)
    {
        static::$config = $config;
    }

    /**
     * Permet de créer et retourner une instance de View
     *
     * @return View
     */
    public static function instance()
    {
        if (static::$instance === null) {
            static::$instance = new self(self::$config);
        }

        return static::$instance;
    }

    /**
     * Permet de faire le rendu d'une vue
     *
     * @param string $viewname
     * @param array $data
     * @param int $code
     * @return string
     * @throws ViewException
     */
    public static function make($viewname, array $data = [], $code = 200)
    {
        static::instance();
        return static::$instance->getTemplate()->render($viewname, $data);
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
     * @return string
     */
    public function __toString()
    {
        return static::make('');
    }
}