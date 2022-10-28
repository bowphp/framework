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
    protected static ?Loader $instance = null;

    /**
     * @var Arraydotify
     */
    protected Arraydotify $config;

    /**
     * @var string
     */
    protected string $base_path;

    /**
     * @var bool
     */
    protected bool $booted = false;

    /**
     * @var array
     */
    private array $middlewares = [];

    /**
     * @var array
     */
    private array $namespaces = [];

    /**
     * @var bool
     */
    private bool $without_session = false;

    /**
     * @param string $base_path
     * @throws
     */
    private function __construct(string $base_path)
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
    public function pushMiddleware(array $middlewares): array
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
    public function getMiddlewares(): array
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
    public function namespaces(): array
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
    public function middlewares(): array
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
    public function configurations(): array
    {
        return [
            //
        ];
    }

    /**
     * Alias of singleton
     *
     * @return Loader
     * @throws ApplicationException
     */
    public static function getInstance(): Loader
    {
        if (is_null(static::$instance)) {
            throw new ApplicationException('The application did not load configurations.');
        }

        return static::$instance;
    }

    /**
     * Define if the configuration going to boot without session manager
     *
     * @return Loader
     */
    public function withoutSession(): Loader
    {
        $this->without_session = true;

        return $this;
    }

    /**
     * Load configuration
     *
     * @return Loader
     */
    public function boot(): Loader
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
     * @param  mixed $value
     * @return mixed
     */
    public function __invoke(string $key, mixed $value = null): mixed
    {
        if ($value == null) {
            return $this->config[$key];
        }

        return $this->config[$key] = $value;
    }

    /**
     * @inheritDoc
     */
    public function offsetExists($offset): bool
    {
        return $this->config->offsetExists($offset);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet($offset):mixed
    {
        return $this->config->offsetExists($offset) ? $this->config[$offset] : null;
    }

    /**
     * @inheritDoc
     */
    public function offsetSet($offset, $value): vod
    {
        $this->config->offsetSet($offset, $value);
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset($offset): void
    {
        $this->config->offsetUnset($offset);
    }
}
