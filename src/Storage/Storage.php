<?php

namespace Bow\Storage;

use BadMethodCallException;
use Bow\Storage\Exception\MountDiskNotFoundException;
use Bow\Storage\Exception\ServiceConfigurationNotFoundException;
use Bow\Storage\Exception\ServiceNotFoundException;

class Storage
{
    /**
     * The data configuration
     *
     * @var array
     */
    private static $config = [];

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
    private static $available_services = [];

    /**
     * Mount disk
     *
     * @param string $mount
     *
     * @return MountFilesystem
     * @throws MountDiskNotFoundException
     */
    public static function mount($mount = null)
    {
        // Use the default disk as fallback
        if (is_null($mount)) {
            if (! is_null(static::$mounted)) {
                return static::$mounted;
            }

            $mount = static::$config['disk']['mount'];
        }

        if (! isset(static::$config['disk']['path'][$mount])) {
            throw new MountDiskNotFoundException('The ' . $mount . ' disk is not define.');
        }

        $config = static::$config['disk']['path'][$mount];

        return static::$mounted = new MountFilesystem($config);
    }

    /**
     * Mount service
     *
     * @param string $service
     *
     * @return mixed
     */
    public static function service(string $service)
    {
        if (!array_key_exists($service, self::$available_services)) {
            throw (new ServiceNotFoundException(sprintf(
                '"%s" is not registered as a service.',
                $service
            )))->setServiceName($service);
        }

        $service_class = static::$available_services[$service];

        $config = static::$config['services'][$service] ?? null;

        if (is_null($config)) {
            throw (new ServiceConfigurationNotFoundException(sprintf(
                '"%s" configuration not found.',
                $service
            )))->setServiceName($service);
        }

        return $service_class::configure($config);
    }

    /**
     * Push a new service who implement
     * the Bow\Storage\Contracts\ServiceInterface
     *
     * @param array $services
     */
    public static function pushService(array $services)
    {
        foreach ($services as $service => $hanlder) {
            static::$available_services[$service] = $hanlder;
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
