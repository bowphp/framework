<?php
namespace Bow\Config;

use Bow\Support\Env;
use Bow\Support\Arraydotify;
use Bow\Application\Exception\ApplicationException;

/**
 * Class AppConfiguratio
 *
 * @author  Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Core
 */
class Config implements \ArrayAccess
{
    /**
     * @var Configuration
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
     * @param string $base_path
     *
     * @throws \Bow\Exception\UtilException
     */
    public function __construct($base_path)
    {
        $this->base_path = $base_path;

        /**
         * Chargement complet de toute la configuration de Bow
         */
        if (file_exists($base_path.'/../.env.json')) {
            Env::load($base_path.'/../.env.json');
        }

        $glob = glob($base_path.'/**.php');
        $config = [];

        foreach ($glob as $file) {
            $key = str_replace('.php', '', basename($file));
            if (in_array($key, ['bootstrap', 'helper', 'classes']) || !file_exists($file)) {
                continue;
            }
            $config[$key] = include $file;
        }

        $this->config = Arraydotify::make($config);

        // Load singleton
        self::$instance = $this;

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
     * @return Config
     */
    public static function configure($base_path)
    {
        if (!self::$instance instanceof Config) {
            new self($base_path);
        }

        return self::$instance;
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
    public function services()
    {
        return [
            //
        ];
    }

    /**
     * Load configuration
     *
     * @return Configuration
     */
    public function boot()
    {
        //
        return $this;
    }

    /**
     * takeInstance singleton
     *
     * @return Config
     * @throws ApplicationException
     */
    public static function singleton()
    {
        if (!self::$instance instanceof Config) {
            throw new ApplicationException('L\'application n\'a pas chargé les confirgurations');
        }

        return self::$instance;
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
        return $this->config[$offset];
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
