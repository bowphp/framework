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
     * UploadFile, fonction permettant de uploader un fichier
     *
     * @param  array    $file      information sur le fichier, $_FILES
     * @param  string   $location
     * @param  int      $size
     * @param  array    $extension
     * @param  callable $cb
     * @return mixed
     * @throws \InvalidArgumentException
     */
    public static function store($file, $location, $size, array $extension, callable $cb)
    {
        if (!is_uploaded_file($file['tmp_name'])) {
            return call_user_func_array($cb, ['error']);
        }

        if (is_string($size)) {
            if (!preg_match('/^([0-9]+)(k|m)$/', strtolower($size), $match)) {
                throw new \InvalidArgumentException('Taille invalide.', E_USER_ERROR);
            }

            $conv = 1;
            array_shift($match);

            if ($match[1] == 'm') {
                $conv = 1000;
            }

            $size = $match[0] * $conv;
        }

        if ($file['size'] > $size) {
            return call_user_func_array($cb, ['size']);
        }

        if (!in_array(pathinfo($file['name'], PATHINFO_EXTENSION), $extension, true)) {
            return call_user_func_array($cb, ['extension']);
        }

        $r = static::copy($file['tmp_name'], $location);

        return call_user_func_array($cb, [$r == true ? false : 'uploaded']);
    }

    /**
     * Ecrire à la suite d'un fichier spécifier
     *
     * @param  string $file    nom du fichier
     * @param  string $content content a ajouter
     * @return bool
     */
    public static function append($file, $content)
    {
        return file_put_contents($file, $content, FILE_APPEND);
    }

    /**
     * Ecrire au début d'un fichier spécifier
     *
     * @param  string $file
     * @param  string $content
     * @return bool
     */
    public static function prepend($file, $content)
    {
        $tmp_content = file_get_contents($file);

        static::put($file, $content);
        return static::append($file, $tmp_content);
    }

    /**
     * Put
     *
     * @param  $file
     * @param  $content
     * @throws ResourceException
     * @return bool
     */
    public static function put($file, $content)
    {
        $file = static::resolvePath($file);
        $dirname = dirname($file);

        static::makeDirectory($dirname);

        return file_put_contents($file, $content);
    }

    /**
     * Supprimer un fichier
     *
     * @param  string $file
     * @return boolean
     */
    public static function delete($file)
    {
        $file = static::resolvePath($file);

        if (is_dir($file)) {
            return @rmdir($file);
        }

        return @unlink($file);
    }

    /**
     * Alias sur readInDir
     *
     * @param  string $dirname
     * @return array
     */
    public static function files($dirname)
    {
        $dirname = static::resolvePath($dirname);
        $directoryContents = glob($dirname."/*");

        return array_filter(
            $directoryContents,
            function ($file) {
                return filetype($file) == "file";
            }
        );
    }

    /**
     * Lire le contenu du dossier
     *
     * @param  string $dirname
     * @return array
     */
    public static function directories($dirname)
    {
        $directoryContents = glob(static::resolvePath($dirname)."/*");

        return array_filter(
            $directoryContents,
            function ($file) {
                return filetype($file) == "dir";
            }
        );
    }

    /**
     * Crée un répertoire
     *
     * @param  string $dirname
     * @param  int    $mode
     * @param  bool   $recursive
     * @return boolean
     */
    public static function makeDirectory($dirname, $mode = 0777, $recursive = false)
    {
        if (is_bool($mode)) {
            $recursive = $mode;
            $mode = 0777;
        }

        return @mkdir(static::resolvePath($dirname), $mode, $recursive);
    }

    /**
     * Récuper le contenu du fichier
     *
     * @param  string $filename
     * @return null|string
     */
    public static function get($filename)
    {
        $filename = static::resolvePath($filename);

        if (!(is_file($filename) && stream_is_local($filename))) {
            return null;
        }

        return file_get_contents($filename);
    }

    /**
     * Copie le contenu d'un fichier source vers un fichier cible.
     *
     * @param  string $targerFile
     * @param  string $sourceFile
     * @return bool
     */
    public static function copy($targerFile, $sourceFile)
    {
        if (!static::exists($targerFile)) {
            throw new \RuntimeException("$targerFile n'exist pas.", E_ERROR);
        }

        if (!static::exists($sourceFile)) {
            static::makeDirectory(dirname($sourceFile), true);
        }

        return file_put_contents($sourceFile, static::get($targerFile));
    }

    /**
     * Rénomme ou déplace un fichier source vers un fichier cible.
     *
     * @param $targerFile
     * @param $sourceFile
     */
    public static function move($targerFile, $sourceFile)
    {
        static::copy($targerFile, $sourceFile);
        static::delete($targerFile);
    }

    /**
     * Vérifie l'existance d'un fichier
     *
     * @param  $filename
     * @return bool
     */
    public static function exists($filename)
    {
        $filename = static::resolvePath($filename);

        if (is_dir($filename)) {
            $tmp = getcwd();
            $r = chdir($filename);
            chdir($tmp);

            return $r;
        }

        return file_exists($filename);
    }

    /**
     * L'extension du fichier
     *
     * @param  $filename
     * @return string
     */
    public static function extension($filename)
    {
        if (static::exists($filename)) {
            return pathinfo(static::resolvePath($filename), PATHINFO_EXTENSION);
        }

        return null;
    }

    /**
     * isFile aliase sur is_file.
     *
     * @param  $filename
     * @return bool
     */
    public static function isFile($filename)
    {
        return is_file(static::resolvePath($filename));
    }

    /**
     * isDirectory aliase sur is_dir.
     *
     * @param  $dirname
     * @return bool
     */
    public static function isDirectory($dirname)
    {
        return is_dir(static::resolvePath($dirname));
    }

    /**
     * Lance la connection au ftp.
     *
     * @param  array $config
     * @return FTP
     */
    public static function ftp($config = null)
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
    public static function s3(array $config = [])
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
     * @param string $mount
     * @return MountFilesystem
     * @throws ResourceException
     */
    public static function mount($mount)
    {
        if (! isset(static::$config['disk']['path'][$mount])) {
            throw new ResourceException('Le dist '.$mount.' n\'est pas défini.');
        }

        return new MountFilesystem(static::$config['disk']['path'][$mount]);
    }

    /**
     * Permet de résolver un path.
     * Donner le chemin absolute d'un path
     *
     * @param  $filename
     * @return string
     */
    public static function resolvePath($filename)
    {
        $mount = static::$config['disk']['mount'];
        $path = realpath(static::$config['disk']['path'][$mount]);

        if (preg_match('~^'.$path.'~', $filename)) {
            return $filename;
        }

        return rtrim($path, '/').'/'.ltrim($filename, '/');
    }

    /**
     * Configure Storage
     *
     * @param array $config
     * @return MountFilesystem
     */
    public static function configure(array $config)
    {
        static::$config = $config;

        return static::mount(static::$config['disk']['mount']);
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
            return call_user_func_array([static::class, $name], $arguments);
        }

        throw new BadMethodCallException("unkdown $name method");
    }
}
