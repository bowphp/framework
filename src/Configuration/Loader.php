<?php

declare(strict_types=1);

namespace Bow\Configuration;

use ArrayAccess;
use Bow\Container\Capsule;
use Bow\Support\Arraydotify;
use Bow\Session\SessionConfiguration;
use Bow\Configuration\EnvConfiguration;
use Bow\Application\Exception\ApplicationException;
use Bow\Container\CompassConfiguration;

class Loader implements ArrayAccess
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
     * @param  string $base_path
     * @throws
     */
    private function __construct(string $base_path)
    {
        $this->base_path = $base_path;
        $this->config = new Arraydotify([]);
    }

    /**
     * Configuration Loader
     *
     * @param  string $base_path
     * @return Loader
     * @throws
     */
    public static function configure(string $base_path): Loader
    {
        if (is_null(static::$instance)) {
            static::$instance = new static($base_path);
        }

        return static::$instance;
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

        $container = Capsule::getInstance();

        // Load the env configuration first
        $env_config = $this->createConfiguration(EnvConfiguration::class, $container);

        $env_config->run();

        // Load the .env or .env.json file
        $this->loadConfigFiles();

        // Configuration of services
        $loaded_configurations = $this->createConfigurations(
            array_merge([CompassConfiguration::class], $this->configurations()),
            $container
        );

        // Load configurations
        $this->runConfirmations($loaded_configurations);

        // Load load events
        $this->loadEvents();

        // Set the load as booted
        $this->booted = true;

        return $this;
    }

    /**
     * Load a configuration service
     *
     * @param  string $configuration_class
     * @param  Capsule $container
     * @return Configuration
     */
    private function createConfiguration(string $configuration_class, Capsule $container): Configuration
    {
        if (!class_exists($configuration_class)) {
            throw new ApplicationException("The configuration class {$configuration_class} does not exists.");
        }

        $configuration = new $configuration_class($container);

        $configuration->create($this);

        return $configuration;
    }

    /**
     * Load configurations
     *
     * @param  array   $configurations
     * @param  Capsule $container
     * @return array
     */
    private function createConfigurations(array $configurations, Capsule $container): array
    {
        $loaded_configurations = [];

        foreach ($configurations as $configuration) {
            if ($this->without_session && $configuration === SessionConfiguration::class) {
                continue;
            }

            $loaded_configurations[] = $this->createConfiguration($configuration, $container);
        }

        return $loaded_configurations;
    }

    /**
     * Run the loaded configurations
     *
     * @param  array $loaded_configurations
     * @return void
     */
    private function runConfirmations(array $loaded_configurations): void
    {
        // Start of services or initial code
        foreach ($loaded_configurations as $service) {
            $service->run();
        }
    }

    /**
     * Load events
     *
     * @return void
     */
    private function loadEvents(): void
    {
        // Bind the define events
        foreach ($this->events() as $name => $handlers) {
            foreach ((array) $handlers as $handler) {
                app_event($name, $handler);
            }
        }
    }

    /**
     * Load the .env file
     *
     * @return void
     * @throws
     */
    private function loadConfigFiles(): void
    {
        /**
         * We load all Bow configuration
         */
        $glob = glob($this->base_path . '/**.php');

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
     * @inheritDoc
     */
    public function offsetExists(mixed $offset): bool
    {
        return $this->config->offsetExists($offset);
    }

    /**
     * @inheritDoc
     */
    public function &offsetGet(mixed $offset): mixed
    {
        if (!$this->config->offsetExists($offset)) {
            $null = null;
            return $null;
        }

        return $this->config[$offset];
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

    /**
     * __invoke
     *
     * @param  string $key
     * @param  mixed  $value
     * @return mixed
     */
    public function __invoke(string $key, mixed $value = null): mixed
    {
        if ($value == null) {
            return $this->config[$key];
        }

        return $this->config[$key] = $value;
    }
}
