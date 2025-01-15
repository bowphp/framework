<?php

declare(strict_types=1);

namespace Bow\Console;

use Bow\Console\Traits\ConsoleTrait;

class Setting
{
    use ConsoleTrait;

    /**
     * The base directory name
     *
     * @var string
     */
    private string $dirname;

    /**
     * The Argument instance
     *
     * @var Argument
     */
    private Argument $arg;

    /**
     * The bootstrap file
     *
     * @var array
     */
    private array $bootstrap = [];

    /**
     * The the public directory
     *
     * @var string
     */
    private string $public_directory;

    /**
     * The the storage directory
     *
     * @var string
     */
    private string $var_directory;

    /**
     * The seeder directory
     *
     * @var string
     */
    private string $seeder_directory;

    /**
     * The migration directory
     *
     * @var string
     */
    private string $migration_directory;

    /**
     * The controller directory
     *
     * @var string
     */
    private string $controller_directory;

    /**
     * The middleware directory
     *
     * @var string
     */
    private string $middleware_directory;

    /**
     * The configuration directory
     *
     * @var string
     */
    private string $configuration_directory;

    /**
     * The validation directory
     *
     * @var string
     */
    private string $validation_directory;

    /**
     * The application directory
     *
     * @var string
     */
    private string $app_directory;

    /**
     * The model directory
     *
     * @var string
     */
    private string $model_directory;

    /**
     * The component directory
     *
     * @var string
     */
    private string $component_directory;

    /**
     * The config directory
     *
     * @var string
     */
    private string $config_directory;

    /**
     * The server filename
     *
     * @var string
     */
    private string $serve_filename;

    /**
     * The exception directory
     *
     * @var string
     */
    private string $exception_directory;

    /**
     * The service directory
     *
     * @var string
     */
    private string $service_directory;

    /**
     * The producer directory
     *
     * @var string
     */
    private string $producer_directory;

    /**
     * The command directory
     *
     * @var string
     */
    private string $command_directory;

    /**
     * The event directory
     *
     * @var string
     */
    private string $event_directory;

    /**
     * The event listener directory
     *
     * @var string
     */
    private string $event_listener_directory;

    /**
     * The namespaces directory
     *
     * @var array
     */
    private array $namespaces = [];

    /**
     * Command constructor.
     *
     * @param string $dirname
     * @return void
     */
    public function __construct(string $dirname)
    {
        $this->dirname = rtrim($dirname, '/');
    }

    /**
     * Set the bootstrap files
     *
     * @param  array $bootstrap
     * @return void
     */
    public function setBootstrap(array $bootstrap): void
    {
        $this->bootstrap = $bootstrap;
    }

    /**
     * Set the server file
     *
     * @param string $serve_filename
     * @return void
     */
    public function setServerFilename(string $serve_filename): void
    {
        $this->serve_filename = $serve_filename;
    }

    /**
     * Set the public directory
     *
     * @param string $public_directory
     * @return void
     */
    public function setPublicDirectory(string $public_directory): void
    {
        $this->public_directory = $public_directory;
    }

    /**
     * Set the config directory
     *
     * @param string $config_directory
     * @return void
     */
    public function setConfigDirectory(string $config_directory): void
    {
        $this->config_directory = $config_directory;
    }

    /**
     * Set the package configuration directory
     *
     * @param string $configuration_directory
     * @return void
     */
    public function setPackageDirectory(string $configuration_directory): void
    {
        $this->configuration_directory = $configuration_directory;
    }

    /**
     * Set the component directory
     *
     * @param string $component_directory
     * @return void
     */
    public function setComponentDirectory(string $component_directory): void
    {
        $this->component_directory = $component_directory;
    }

    /**
     * Set the migration directory
     *
     * @param string $migration_directory
     * @return void
     */
    public function setMigrationDirectory(string $migration_directory): void
    {
        $this->migration_directory = $migration_directory;
    }

    /**
     * Set the seeder directory
     *
     * @param string $seeder_directory
     * @return void
     */
    public function setSeederDirectory(string $seeder_directory): void
    {
        $this->seeder_directory = $seeder_directory;
    }

    /**
     * Set the controller directory
     *
     * @param string $controller_directory
     * @return void
     */
    public function setControllerDirectory(string $controller_directory): void
    {
        $this->controller_directory = $controller_directory;
    }

    /**
     * Set the validation directory
     *
     * @param string $validation_directory
     * @return void
     */
    public function setValidationDirectory(string $validation_directory): void
    {
        $this->validation_directory = $validation_directory;
    }

    /**
     * Set the middleware directory
     *
     * @param string $middleware_directory
     * @return void
     */
    public function setMiddlewareDirectory(string $middleware_directory): void
    {
        $this->middleware_directory = $middleware_directory;
    }

    /**
     * Set the application directory
     *
     * @param string $app_directory
     * @return void
     */
    public function setApplicationDirectory(string $app_directory): void
    {
        $this->app_directory = $app_directory;
    }

    /**
     * Set the model directory
     *
     * @param string $model_directory
     * @return void
     */
    public function setModelDirectory(string $model_directory): void
    {
        $this->model_directory = $model_directory;
    }

    /**
     * Set the var directory
     *
     * @param string $var_directory
     * @return void
     */
    public function setVarDirectory(string $var_directory): void
    {
        $this->var_directory = $var_directory;
    }

    /**
     * Set the exception directory
     *
     * @param string $exception_directory
     * @return void
     */
    public function setExceptionDirectory(string $exception_directory): void
    {
        $this->exception_directory = $exception_directory;
    }

    /**
     * Set the service directory
     *
     * @param string $service_directory
     * @return void
     */
    public function setServiceDirectory(string $service_directory): void
    {
        $this->service_directory = $service_directory;
    }

    /**
     * Set the producer directory
     *
     * @param string $producer_directory
     * @return void
     */
    public function setProducerDirectory(string $producer_directory): void
    {
        $this->producer_directory = $producer_directory;
    }

    /**
     * Set the command directory
     *
     * @param string $command_directory
     * @return void
     */
    public function setCommandDirectory(string $command_directory): void
    {
        $this->command_directory = $command_directory;
    }

    /**
     * Set the event directory
     *
     * @param string $event_directory
     * @return void
     */
    public function setEventDirectory(string $event_directory): void
    {
        $this->event_directory = $event_directory;
    }

    /**
     * Set the event listener directory
     *
     * @param string $event_listener_directory
     * @return void
     */
    public function setEventListenerDirectory(string $event_listener_directory): void
    {
        $this->event_listener_directory = $event_listener_directory;
    }

    /**
     * Set the namespaces
     *
     * @param array $namespaces
     * @return void
     */
    public function setNamespaces(array $namespaces): void
    {
        foreach ($namespaces as $key => $namespace) {
            $this->namespaces[$key] = $namespace;
        }
    }

    /**
     * Get the namespaces
     *
     * @return array
     */
    public function getNamespaces(): array
    {
        return $this->namespaces;
    }

    /**
     * Get the var directory
     *
     * @return string
     */
    public function getVarDirectory(): string
    {
        return $this->var_directory;
    }

    /**
     * Get the component directory
     *
     * @return string
     */
    public function getComponentDirectory(): string
    {
        return $this->component_directory;
    }

    /**
     * Get the config directory
     *
     * @return string
     */
    public function getConfigDirectory(): string
    {
        return $this->config_directory;
    }

    /**
     * Get the package configuration directory
     *
     * @return string
     */
    public function getPackageDirectory(): string
    {
        return $this->configuration_directory;
    }

    /**
     * Get the migration directory
     *
     * @return string
     */
    public function getMigrationDirectory(): string
    {
        return $this->migration_directory;
    }

    /**
     * Get the seeder directory
     *
     * @return string
     */
    public function getSeederDirectory(): string
    {
        return $this->seeder_directory;
    }

    /**
     * Get the validation directory
     *
     * @return string
     */
    public function getValidationDirectory(): string
    {
        return $this->validation_directory;
    }

    /**
     * Get the service directory
     *
     * @return string
     */
    public function getServiceDirectory(): string
    {
        return $this->service_directory;
    }

    /**
     * Get the producer directory
     *
     * @return string
     */
    public function getProducerDirectory(): string
    {
        return $this->producer_directory;
    }

    /**
     * Get the command directory
     *
     * @return string
     */
    public function getCommandDirectory(): string
    {
        return $this->command_directory;
    }

    /**
     * Get the event directory
     *
     * @return string
     */
    public function getEventDirectory(): string
    {
        return $this->event_directory;
    }

    /**
     * Get the event listener directory
     *
     * @return string
     */
    public function getEventListenerDirectory(): string
    {
        return $this->event_listener_directory;
    }

    /**
     * Get the service directory
     *
     * @return string
     */
    public function getMiddlewareDirectory(): string
    {
        return $this->middleware_directory;
    }

    /**
     * Get the model directory
     *
     * @return string
     */
    public function getModelDirectory(): string
    {
        return $this->model_directory;
    }

    /**
     * Get the controller directory
     *
     * @return string
     */
    public function getControllerDirectory(): string
    {
        return $this->controller_directory;
    }

    /**
     * Get the app directory
     *
     * @return string
     */
    public function getApplicationDirectory(): string
    {
        return $this->app_directory;
    }

    /**
     * Get base directory name
     *
     * @return string
     */
    public function getBaseDirectory(): string
    {
        return $this->dirname;
    }

    /**
     * Get the bootstrap files
     *
     * @return array
     */
    public function getBootstrap(): array
    {
        return $this->bootstrap;
    }

    /**
     * Get the local server file
     *
     * @return string
     */
    public function getServerFilename(): string
    {
        return $this->serve_filename;
    }

    /**
     * Get the public base directory
     *
     * @return string
     */
    public function getPublicDirectory(): string
    {
        return $this->public_directory;
    }

    /**
     * Get the exception directory
     *
     * @return string
     */
    public function getExceptionDirectory(): string
    {
        return $this->exception_directory;
    }
}
