<?php
namespace Bow\Application;

use Bow\Support\Env;
use Bow\Support\Arraydotify;
use Bow\Application\Exception\ApplicationException;

/**
 * Class AppConfiguratio
 *
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Core
 */
class Configuration implements \ArrayAccess
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
     * @param string $base_path
     *
     * @throws \Bow\Exception\UtilException
     */
    private final function __construct($base_path)
    {
        /**
         * Chargement complet de toute la configuration de Bow
         */
        if (file_exists($base_path.'/.env.json')) {
            Env::load($base_path.'/.env.json');
        }

        $glob = glob($base_path.'/config/**.php');
        $config = [];

        foreach ($glob as $file) {
            $key = str_replace('.php', '', basename($file));
            if (in_array($key, ['bootstrap', 'helper', 'classes']) || !file_exists($file)) {
                continue;
            }
            $config[$key] = require $file;
        }

        $this->config = Arraydotify::make($config);
    }

    /**
     * Ferméture de la fonction magic __clone pour optimizer le singleton
     */
    private final function __clone() { }

    /**
     * takeInstance singleton
     *
     * @param string $base_path
     * @return Configuration
     */
    public static function configure($base_path)
    {
        if (!self::$instance instanceof Configuration) {
            self::$instance = new self($base_path);
        }

        return self::$instance;
    }

    /**
     * takeInstance singleton
     *
     * @return Configuration
     * @throws ApplicationException
     */
    public static function singleton()
    {
        if (!self::$instance instanceof Configuration) {
            throw new ApplicationException('L\'application n\'a pas chargé les confirgurations');
        }

        return self::$instance;
    }

    /**
     * __invoke
     *
     * @param $key
     * @param null $value
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
        return $this->config->offsetGet($offset);
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