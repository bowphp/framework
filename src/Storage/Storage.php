<?php

declare(strict_types=1);

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
    private static array $available_services_driviers = [
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

        if (!isset(static::$config['disk']['path'][$disk])) {
            throw new DiskNotFoundException('The ' . $disk . ' disk is not define.');
        }

        $config = static::$config['disk']['path'][$disk];

        return static::$disk = new DiskFilesystemService($config);
    }

    /**
     * Mount service
     *
     * @param string $service
     * @return FTPService|S3Service
     */
    public static function service(string $service)
    {
        $config = static::$config['services'][$service] ?? null;

        if (is_null($config)) {
            throw (new ServiceConfigurationNotFoundException(sprintf(
                '"%s" configuration not found.',
                $service
            )))->setService($service);
        }

        $driver = $config["driver"] ?? null;

        if (is_null($driver)) {
            throw (new ServiceNotFoundException(sprintf(
                '"%s" driver is not support.',
                $driver
            )))->setService($driver);
        }

        if (!array_key_exists($driver, self::$available_services_driviers)) {
            throw (new ServiceNotFoundException(sprintf(
                '"%s" is not registered as a service.',
                $driver
            )))->setService($driver);
        }

        $service_class = static::$available_services_driviers[$driver];

        return $service_class::configure($config);
    }

    /**
     * Push a new service who implement
     * the Bow\Storage\Contracts\ServiceInterface
     *
     * @param array $drivers
     */
    public static function pushService(array $drivers)
    {
        foreach ($drivers as $driver => $hanlder) {
            if (isset(static::$available_services_driviers[$driver])) {
                throw new InvalidArgumentException("The $driver is already define");
            }

            static::$available_services_driviers[$driver] = $hanlder;
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
