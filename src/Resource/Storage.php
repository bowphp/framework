<?php
namespace Bow\Resource;

use BadMethodCallException;
use Bow\Resource\Ftp\FTP;
use Bow\Resource\AWS\AwsS3Client;
use Bow\Resource\Exception\ResourceException;

/**
 * Class Storage
 *
 * @author  Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Support
 */
class Storage
{
    /**
     * @var array
     */
    private static $config;

    /**
     * @var FTP
     */
    private static $ftp;

    /**
     * @var AwsS3Client
     */
    private static $s3;

    /**
     * @var MountFilesystem
     */
    private static $mounted;

    /**
     * @var array
     */
    const AVAILABLE_SERIVCES = ['ftp', 's3'];

    /**
     * Lance la connection au ftp.
     *
     * @param  array $config
     * @return FTP
     * @throws
     */
    private static function ftp($config = null)
    {
        if (static::$ftp instanceof FTP) {
            return static::$ftp;
        }

        if ($config == null) {
            $config = static::$config['ftp'];
        }

        if (!isset($config['tls'])) {
            $config['tls'] = false;
        }

        if (!isset($config['timeout'])) {
            $config['timeout'] = 90;
        }

        static::$ftp = new FTP();

        static::$ftp->connect($config['hostname'], $config['username'], $config['password'], $config['port'], $config['tls'], $config['timeout']);

        if (isset($config['root'])) {
            if ($config['root'] !== null) {
                static::$ftp->chdir($config['root']);
            }
        }

        return static::$ftp;
    }

    /**
     * @param array $config
     * @return AwsS3Client
     */
    private static function s3(array $config = [])
    {
        if (static::$s3 instanceof AwsS3Client) {
            return static::$s3;
        }

        if (empty($config)) {
            $config = isset(static::$config['s3']) ? static::$config['s3'] : [];
        }

        static::$s3 = new AwsS3Client($config);

        return static::$s3;
    }

    /**
     * Mount disk
     *
     * @param string $mount
     * @return MountFilesystem
     * @throws ResourceException
     */
    public static function mount($mount)
    {
        if (! isset(static::$config['disk']['path'][$mount])) {
            throw new ResourceException('Le disque '.$mount.' n\'est pas défini.');
        }

        return new MountFilesystem(static::$config['disk']['path'][$mount]);
    }

    /**
     * Mount service
     *
     * @param string $service
     * @return mixed
     */
    public static function service($service)
    {
        if (! in_array($service, static::AVAILABLE_SERIVCES)) {
            throw new \InvalidArgumentException(sprintf('Le service "%s" est invalide', $service));
        }

        return static::$service();
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
        if (method_exists(static::class, $name)) {
            return call_user_func_array([static::$mounted, $name], $arguments);
        }

        throw new BadMethodCallException("unkdown $name method");
    }
}
