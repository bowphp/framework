<?php

namespace Bow\Configuration;

use Bow\Application\Exception\ApplicationException;
use Bow\Container\Capsule;
use Bow\Support\Arraydotify;
use Bow\Support\Env;

class Loader implements \ArrayAccess
{
    /**
     * @var Loader
     */
    protected static $instance;

    /**
     * @var Arraydotify
     */
    protected $config;

    /**
     * @var string
     */
    protected $base_path;

    /**
     * @var bool
     */
    protected $booted = false;

    /**
     * @var array
     */
    private $middlewares = [];

    /**
     * @var array
     */
    private $namespaces = [];

    /**
     * @var bool
     */
    private $without_session = false;

    /**
     * @param string $base_path
     * @throws
     */
    private function __construct($base_path)
    {
        $this->base_path = $base_path;

        /**
         * We load all env file
         */
        if (file_exists($base_path . '/../.env.json')) {
            Env::load($base_path . '/../.env.json');
        }

        /**
         * We load all Bow configuration
         */
        $glob = glob($base_path . '/**.php');

        $config = [];

        foreach ($glob as $file) {
            $key = str_replace('.php', '', basename($file));

            if ($key == 'helper' || !file_exists($file)) {
                continue;
            }

            $config[$key] = include $file;
        }

        $this->config = Arraydotify::make($config);
    }

    /**
     * Closing the magic __clone function to optimize the singleton
     */
    final private function __clone()
    {
    }

    /**
     * Configuration Loader
     *
     * @param string $base_path
     * @return Loader
     * @throws
     */
    public static function configure($base_path)
    {
        if (!static::$instance instanceof Loader) {
            static::$instance = new static($base_path);
        }

        return static::$instance;
    }

    /**
     * Push middlewares
     *
     * @param array $middlewares
     */
    public function pushMiddleware(array $middlewares)
    {
        foreach ($middlewares as $key => $middleware) {
            $this->middlewares[$key] = $middleware;
        }
    }

    /**
     * Middleware collection
     *
     * @return array
     */
    public function getMiddlewares()
    {
        $middlewares = $this->middlewares();

        foreach ($middlewares as $key => $middleware) {
            $this->middlewares[$key] = $middleware;
        }

        return $this->middlewares;
    }

    /**
     * Get app namespace
     *
     * @return array
     */
    public function namespaces()
    {
        return [
            //
        ];
    }

    /**
     * Middleware collection
     *
     * @return array
     */
    public function middlewares()
    {
        return [
            //
        ];
    }

    /**
     * Load services
     *
     * @return array
     */
    public function configurations()
    {
        return [
            //
        ];
    }

    /**
     * Alias of singleton
     *
     * @return Loader
     * @throws
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            throw new ApplicationException('The application did not load configurations.');
        }

        return static::$instance;
    }

    /**
     * Define if the configuration going to boot without session manager
     *
     * @return void
     */
    public function withoutSession()
    {
        $this->without_session = true;

        return $this;
    }

    /**
     * Load configuration
     *
     * @return Loader
     */
    public function boot()
    {
        if ($this->booted) {
            return $this;
        }

        $services = $this->configurations();

        $services[] = \Bow\Container\ContainerConfiguration::class;

        $service_collection = [];

        $container = Capsule::getInstance();

        // Configuration of services
        foreach ($services as $service) {
            if ($this->without_session && $service == \Bow\Session\SessionConfiguration::class) {
                continue;
            }

            if (class_exists($service, true)) {
                $class = new $service($container);

                $class->create($this);

                $service_collection[] = $class;
            }
        }

        // Start of services or initial code
        foreach ($service_collection as $service) {
            $service->run();
        }

        $this->booted = true;

        return $this;
    }

    /**
     * __invoke
     *
     * @param  $key
     * @param  null $value
     * @return mixed
     */
    public function __invoke($key, $value = null)
    {
        if ($value == null) {
            return $this->config[$key];
        }

        return $this->config[$key] = $value;
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset)
    {
        return $this->config->offsetExists($offset);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset)
    {
        return $this->config->offsetExists($offset) ? $this->config[$offset] : null;
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value)
    {
        $this->config->offsetSet($offset, $value);
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset)
    {
        $this->config->offsetUnset($offset);
    }
}
