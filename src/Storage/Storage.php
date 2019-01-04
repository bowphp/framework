<?php

namespace Bow\Storage;

use BadMethodCallException;
use Bow\Storage\AWS\AwsS3Client;
use Bow\Storage\Exception\ResourceException;
use Bow\Storage\Ftp\FTP;
use Bow\Storage\Exception\ServiceNotFoundException;

class Storage
{
    /**
     * The data configuration
     * 
     * @var array
     */
    private static $config;

    /**
     * The disk mounting 
     * 
     * @var MountFilesystem
     */
    private static $mounted;

    /**
     * The service lists
     * 
     * @var array
     */
    private static $available_serivces = [];

    /**
     * Mount disk
     *
     * @param string $mount
     * @return MountFilesystem
     * @throws ResourceException
     */
    public static function mount($mount = null)
    {
        if (is_null($mount)) {
            if (! is_null(static::$mounted)) {
                return static::$mounted;
            }

            $mount = static::$config['disk']['mount'];
        }

        if (! isset(static::$config['disk']['path'][$mount])) {
            throw new ResourceException('Le disque '.$mount.' n\'est pas défini.');
        }

        return static::$mounted = new MountFilesystem(static::$config['disk']['path'][$mount]);
    }

    /**
     * Mount service
     *
     * @param string $service
     * @return mixed
     */
    public static function service($service)
    {
        if (! in_array($service, static::$available_serivces)) {
            throw new ServiceNotFoundException(sprintf('This "%s" service is invalid.', $service));
        }

        $service = static::$available_serivces[$service];

        return $service::config(static::$config[$service]);
    }

    /**
     * Push a new serive who implement the Bow\Storage\Contracts\ServiceInterface
     * contracts
     * 
     * @param array $services
     */
    public static function pushService(array $services)
    {
        foreach ($services as $service => $hanlder) {
            static::$available_serivces[$service] = $hanlder;
        }
    }

    /**
     * Configure Storage
     *
     * @param array $config
     * @return MountFilesystem
     * @throws
     */
    public static function configure(array $config)
    {
        static::$config = $config;

        if (is_null(static::$mounted)) {
            static::$mounted = static::mount($config['disk']['mount']);
        }

        return static::$mounted;
    }

    /**
     * __call
     *
     * @param  string $name
     * @param  array  $arguments
     * @return mixed
     */
    public function __call($name, array $arguments)
    {
        if (method_exists(static::$mounted, $name)) {
            return call_user_func_array([static::$mounted, $name], $arguments);
        }

        throw new BadMethodCallException("unkdown $name method");
    }

    /**
     * __callStatic
     *
     * @param  string $name
     * @param  array  $arguments
     * @return mixed
     */
    public static function __callStatic($name, array $arguments)
    {
        if (method_exists(static::$mounted, $name)) {
            return call_user_func_array([static::$mounted, $name], $arguments);
        }

        throw new BadMethodCallException("unkdown $name method");
    }
}
