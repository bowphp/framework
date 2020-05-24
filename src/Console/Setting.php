<?php

namespace Bow\Console;

use Bow\Support\Collection;
use Bow\Support\Str;

class Setting
{
    use ConsoleInformation;

    /**
     * The base directory name
     *
     * @var string
     */
    private $dirname;

    /**
     * The ArgOption instance
     *
     * @var array
     */
    private $arg;

    /**
     * The bootstrap file
     *
     * @var array
     */
    private $bootstrap = [];

    /**
     * The the public directory
     *
     * @var string
     */
    private $public_directory;

    /**
     * The the storage directory
     *
     * @var string
     */
    private $var_directory;

    /**
     * The seeder directory
     *
     * @var string
     */
    private $seeder_directory;

    /**
     * The migration directory
     *
     * @var string
     */
    private $migration_directory;

    /**
     * The controller directory
     *
     * @var string
     */
    private $controller_directory;

    /**
     * The middleware directory
     *
     * @var string
     */
    private $middleware_directory;

    /**
     * The configuration directory
     *
     * @var string
     */
    private $configuration_directory;

    /**
     * The validation directory
     *
     * @var string
     */
    private $validation_directory;

    /**
     * The application directory
     *
     * @var string
     */
    private $app_directory;

    /**
     * The model directory
     *
     * @var string
     */
    private $model_directory;

    /**
     * The component directory
     *
     * @var string
     */
    private $component_directory;

    /**
     * The config directory
     *
     * @var string
     */
    private $config_directory;

    /**
     * The server filename
     *
     * @var string
     */
    private $serve_filename;

    /**
     * The exception directory
     *
     * @var string
     */
    private $exception_directory;

    /**
     * The service directory
     *
     * @var string
     */
    private $service_directory;

    /**
     * The namesapces directory
     *
     * @var array
     */
    private $namespaces = [];

    /**
     * Command constructor.
     *
     * @param string $dirname
     * @return void
     */
    public function __construct($dirname)
    {
        $this->dirname = rtrim($dirname, '/');
    }

    /**
     * The arg option
     * @return ArgOption
     */
    public function getArgOption()
    {
        return $this->arg;
    }

    /**
     * Set the bootstrap files
     *
     * @param  array $bootstrap
     * @return void
     */
    public function setBootstrap(array $bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    /**
     * Set the server file
     *
     * @param string $serve_filename
     * @return void
     */
    public function setServerFilename($serve_filename)
    {
        $this->serve_filename = $serve_filename;
    }

    /**
     * Set the public directory
     *
     * @param string $public_directory
     * @return void
     */
    public function setPublicDirectory($public_directory)
    {
        $this->public_directory = $public_directory;
    }

    /**
     * Set the config directory
     *
     * @param string $config_directory
     * @return void
     */
    public function setConfigDirectory($config_directory)
    {
        $this->config_directory = $config_directory;
    }

    /**
     * Set the package configuration directory
     *
     * @param string $configuration_directory
     * @return void
     */
    public function setPackageDirectory($configuration_directory)
    {
        $this->configuration_directory = $configuration_directory;
    }

    /**
     * Set the component directory
     *
     * @param string $component_directory
     * @return void
     */
    public function setComponentDirectory($component_directory)
    {
        $this->component_directory = $component_directory;
    }

    /**
     * Set the migration directory
     *
     * @param string $migration_directory
     * @return void
     */
    public function setMigrationDirectory($migration_directory)
    {
        $this->migration_directory = $migration_directory;
    }

    /**
     * Set the seeder directory
     *
     * @param string $seeder_directory
     * @return void
     */
    public function setSeederDirectory($seeder_directory)
    {
        $this->seeder_directory = $seeder_directory;
    }

    /**
     * Set the controller directory
     *
     * @param string $controller_directory
     * @return void
     */
    public function setControllerDirectory($controller_directory)
    {
        $this->controller_directory = $controller_directory;
    }

    /**
     * Set the validation directory
     *
     * @param string $validation_directory
     * @return void
     */
    public function setValidationDirectory($validation_directory)
    {
        $this->validation_directory = $validation_directory;
    }

    /**
     * Set the middleware directory
     *
     * @param string $middleware_directory
     * @return void
     */
    public function setMiddlewareDirectory($middleware_directory)
    {
        $this->middleware_directory = $middleware_directory;
    }

    /**
     * Set the application directory
     *
     * @param string $app_directory
     * @return void
     */
    public function setApplicationDirectory($app_directory)
    {
        $this->app_directory = $app_directory;
    }

    /**
     * Set the model directory
     *
     * @param string $model_directory
     * @return void
     */
    public function setModelDirectory($model_directory)
    {
        $this->model_directory = $model_directory;
    }

    /**
     * Set the var directory
     *
     * @param string $var_directory
     * @return void
     */
    public function setVarDirectory($var_directory)
    {
        $this->var_directory = $var_directory;
    }

    /**
     * Set the exception directory
     *
     * @param string $exception_directory
     * @return void
     */
    public function setExceptionDirectory($exception_directory)
    {
        $this->exception_directory = $exception_directory;
    }

    /**
     * Set the service directory
     *
     * @param string $service_directory
     * @return void
     */
    public function setServiceDirectory($service_directory)
    {
        $this->service_directory = $service_directory;
    }

    /**
     * Set the namespaces
     *
     * @param array $namespaces
     * @return void
     */
    public function setNamespaces(array $namespaces)
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
    public function getNamespaces()
    {
        return $this->namespaces;
    }

    /**
     * Get the var directory
     *
     * @return string
     */
    public function getVarDirectory()
    {
        return $this->var_directory;
    }

    /**
     * Get the component directory
     *
     * @return string
     */
    public function getComponentDirectory()
    {
        return $this->component_directory;
    }

    /**
     * Get the config directory
     *
     * @return string
     */
    public function getConfigDirectory()
    {
        return $this->config_directory;
    }

    /**
     * Get the package configuration directory
     *
     * @return string
     */
    public function getPackageDirectory()
    {
        return $this->configuration_directory;
    }

    /**
     * Get the migration directory
     *
     * @return string
     */
    public function getMigrationDirectory()
    {
        return $this->migration_directory;
    }

    /**
     * Get the seeder directory
     *
     * @return string
     */
    public function getSeederDirectory()
    {
        return $this->seeder_directory;
    }

    /**
     * Get the validation directory
     *
     * @return string
     */
    public function getValidationDirectory()
    {
        return $this->validation_directory;
    }

    /**
     * Get the service directory
     *
     * @return string
     */
    public function getServiceDirectory()
    {
        return $this->service_directory;
    }

    /**
     * Get the service directory
     *
     * @return string
     */
    public function getMiddlewareDirectory()
    {
        return $this->middleware_directory;
    }

    /**
     * Get the model directory
     *
     * @return string
     */
    public function getModelDirectory()
    {
        return $this->model_directory;
    }

    /**
     * Get the controller directory
     *
     * @return string
     */
    public function getControllerDirectory()
    {
        return $this->controller_directory;
    }

    /**
     * Get the app directory
     *
     * @return string
     */
    public function getApplicationDirectory()
    {
        return $this->app_directory;
    }

    /**
     * Get base directory name
     *
     * @return string
     */
    public function getBaseDirectory()
    {
        return $this->dirname;
    }

    /**
     * Get the bootstrap files
     *
     * @return array
     */
    public function getBootstrap()
    {
        return $this->bootstrap;
    }

    /**
     * Get the local server file
     *
     * @return void
     */
    public function getServerFilename()
    {
        return $this->serve_filename;
    }

    /**
     * Get the public base directory
     *
     * @return void
     */
    public function getPublicDirectory()
    {
        return $this->public_directory;
    }

    /**
     * Get the exception directory
     *
     * @return string
     */
    public function getExceptionDirectory()
    {
        return $this->exception_directory;
    }
}
