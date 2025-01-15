<?php

declare(strict_types=1);

namespace Bow\Configuration;

use Bow\Event\Event;
use Bow\Support\Env;
use Bow\Container\Capsule;
use Bow\Support\Arraydotify;
use Bow\Application\Exception\ApplicationException;

class Loader implements \ArrayAccess
{
    /**
     * @var ?Loader
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

            if ($key == 'helper' || $key == 'helpers' || !file_exists($file)) {
                continue;
            }

            // Laad the configuration file content
            $config[$key] = include $file;
        }

        $this->config = Arraydotify::make($config);
    }

    /**
     * Check if php running env is cli
     *
     * @return bool
     */
    public function isCli(): bool
    {
        return php_sapi_name() == 'cli';
    }

    /**
     * Get the base path
     *
     * @return string
     */
    public function getBasePath(): string
    {
        return $this->base_path;
    }

    /**
     * Configuration Loader
     *
     * @param string $base_path
     * @return Loader
     * @throws
     */
    public static function configure(string $base_path): Loader
    {
        if (!static::$instance instanceof Loader) {
            static::$instance = new static($base_path);
        }

        return static::$instance;
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
     * Namespaces collection
     *
     * @return array
     */
    public function getNamespaces(): array
    {
        $namespaces = $this->namespaces();

        foreach ($namespaces as $key => $namespace) {
            $this->namespaces[$key] = $namespace;
        }

        return $this->namespaces;
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
     * Load events
     *
     * @return array
     */
    public function events(): array
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

        $services = array_merge(
            [\Bow\Container\ContainerConfiguration::class],
            $this->configurations(),
        );

        $service_collection = [];

        $container = Capsule::getInstance();

        // Configuration of services
        foreach ($services as $service) {
            if ($this->without_session && $service === \Bow\Session\SessionConfiguration::class) {
                continue;
            }

            if (!class_exists($service, true)) {
                continue;
            }

            $service_instance = new $service($container);
            $service_instance->create($this);
            $service_collection[] = $service_instance;
        }

        // Start of services or initial code
        foreach ($service_collection as $service) {
            $service->run();
        }

        // Bind the define events
        foreach ($this->events() as $name => $handlers) {
            $handlers = (array) $handlers;
            foreach ($handlers as $handler) {
                Event::on($name, $handler);
            }
        }

        // Set the load as booted
        $this->booted = true;

        return $this;
    }

    /**
     * __invoke
     *
     * @param string $key
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
    public function offsetExists(mixed $offset): bool
    {
        return $this->config->offsetExists($offset);
    }

    /**
     * @inheritDoc
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->config[$offset] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->config->offsetSet($offset, $value);
    }

    /**
     * @inheritDoc
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->config->offsetUnset($offset);
    }
}
