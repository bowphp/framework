<?php

namespace Bow\Storage;

use BadMethodCallException;
use InvalidArgumentException;
use Bow\Storage\Exception\DiskNotFoundException;
use Bow\Storage\Exception\ServiceConfigurationNotFoundException;
use Bow\Storage\Exception\ServiceNotFoundException;
use Bow\Storage\Service\DiskFilesystemService;
use Bow\Storage\Service\FTPService;
use Bow\Storage\Service\S3Service;

class Storage
{
    /**
     * The data configuration
     *
     * @var array
     */
    private static array $config = [];

    /**
     * The disk mounting
     *
     * @var DiskFilesystemService
     */
    private static ?DiskFilesystemService $disk = null;

    /**
     * The service lists
     *
     * @var array
     */
    private static array $available_services = [
        'ftp' => FTPService::class,
        's3' => S3Service::class,
    ];

    /**
     * Mount disk
     *
     * @param string $disk
     *
     * @return DiskFilesystemService
     * @throws DiskNotFoundException
     */
    public static function disk(?string $disk = null)
    {
        // Use the default disk as fallback
        if (is_null($disk)) {
            if (! is_null(static::$disk)) {
                return static::$disk;
            }

            $disk = static::$config['disk']['mount'];
        }

        if (! isset(static::$config['disk']['path'][$disk])) {
            throw new DiskNotFoundException('The '.$disk.' disk is not define.');
        }

        $config = static::$config['disk']['path'][$disk];

        return static::$disk = new DiskFilesystemService($config);
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
            if (isset(static::$available_services[$service])) {
                throw new InvalidArgumentException("The $service is already define");
            }

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

        if (is_null(static::$disk)) {
            static::$disk = static::disk($config['disk']['mount']);
        }

        return static::$disk;
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
        if (method_exists(static::$disk, $name)) {
            return call_user_func_array([static::$disk, $name], $arguments);
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
        if (method_exists(static::$disk, $name)) {
            return call_user_func_array([static::$disk, $name], $arguments);
        }

        throw new BadMethodCallException("unkdown $name method");
    }
}
