<?php

namespace Bow\Configuration;

use Bow\Support\Capsule;
use Bow\Support\Env;
use Bow\Support\Arraydotify;
use Bow\Application\Exception\ApplicationException;

class Loader implements \ArrayAccess
{
    /**
     * @var Loader
     */
    private static $instance;

    /**
     * @var Arraydotify
     */
    private $config;

    /**
     * @var string
     */
    protected $base_path;

    /**
     * @var bool
     */
    private $booted = false;

    /**
     * @param string $base_path
     * @throws
     */
    private function __construct($base_path)
    {
        $this->base_path = $base_path;

        /**
         * Chargement complet de toute la configuration de Bow
         */
        if (file_exists($base_path . '/../.env.json')) {
            Env::load($base_path . '/../.env.json');
        }

        $glob = glob($base_path . '/**.php');

        $config = [];

        foreach ($glob as $file) {
            $key = str_replace('.php', '', basename($file));

            if (in_array($key, ['bootstrap', 'helper', 'classes']) || !file_exists($file)) {
                continue;
            }

            $config[$key] = include $file;
        }

        $this->config = Arraydotify::make($config);
    }

    /**
     * Ferméture de la fonction magic __clone pour optimizer le singleton
     */
    final private function __clone()
    {
    }

    /**
     * takeInstance singleton
     *
     * @param  string $base_path
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
     * Load serivces
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
     * Alias de singleton
     *
     * @return Loader
     * @throws
     */
    public static function getInstance()
    {
        if (is_null(static::$instance)) {
            throw new ApplicationException('L\'application n\'a pas chargé les confirgurations');
        }

        return static::$instance;
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

        $service_collection = [];

        $container = Capsule::getInstance();

        // Configuration des services
        foreach ($services as $service) {
            if (class_exists($service, true)) {
                $class = new $service($container);

                $class->create($this);

                $service_collection[] = $class;
            }
        }

        // Démarage des services ou code d'initial
        foreach ($service_collection as $service) {
            $service->run();
        }

        $this->booted = true;

        return $this;
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
