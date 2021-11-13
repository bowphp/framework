<?php

namespace Bow\Storage\Service;

use Aws\S3\S3Client;
use Bow\Http\UploadFile;
use Bow\Storage\Contracts\ServiceInterface;

class S3Service implements ServiceInterface
{
    /**
     * The S3Service instance
     *
     * @var S3Service
     */
    private static $instance;

    /**
     * The attribute define the service configuration
     *
     * @var array
     */
    private $config;

    /**
     * The attribute define the guzzle http configuration
     *
     * @var S3Client
     */
    private $client;

    /**
     * S3Service constructor
     *
     * @param array $config
     * @return void
     */
    private function __construct(array $config)
    {
        $this->config = $config;

        $this->client = new S3Client($config);
    }

    /**
     * S3Service Configuration
     *
     * @param array $config
     *
     * @return S3Service
     */
    public static function configure(array $config)
    {
        if (is_null(static::$instance)) {
            static::$instance = new S3Service($config);
        }

        return static::$instance;
    }

    /**
     * Get S3Service
     *
     * @return S3Service
     */
    public static function getInstance()
    {
        return static::$instance;
    }

    /**
     * Function to upload a file
     *
     * @param  UploadFile  $file
     * @param  string  $location
     * @param  array   $option
     *
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function store(UploadFile $file, $location = null, array $option = [])
    {
        // TODO
    }

    /**
     * Add content after the contents of the file
     *
     * @param  string $file
     * @param  string $content
     * @return bool
     */
    public function append($file, $content)
    {
        // TODO
    }

    /**
     * Add content before the contents of the file
     *
     * @param  string $file
     * @param  string $content
     * @return bool
     * @throws
     */
    public function prepend($file, $content)
    {
        // TODO
    }

    /**
     * Put other file content in given file
     *
     * @param string $file
     * @param string $content
     * @param array $option
     *
     * @return bool
     */
    public function put($file, $content, array $option = [])
    {
        if (isset($option['bucket'])) {
            $bucket = $option['bucket'];
        } else {
            $bucket = $this->config['bucket'];
        }

        $this->client->putObject(array(
            'Bucket' => $bucket,
            'Key'    => $file,
            'Body'   => $content
        ));
    }

    /**
     * Delete file or directory
     *
     * @param  string $file
     * @return boolean
     */
    public function delete($file)
    {
        $result = $this->client->deleteObject(array(
            'Bucket' => $this->config['bucket'],
            'Key' => $file
        ));
    }

    /**
     * List the files of a folder passed as a parameter
     *
     * @param  string $dirname
     * @return array
     */
    public function files($dirname)
    {
        // TODO
    }

    /**
     * List the folder of a folder passed as a parameter
     *
     * @param  string $dirname
     * @return array
     */
    public function directories($dirname)
    {
        // TODO
    }

    /**
     * Create a directory
     *
     * @param  string $dirname
     * @param  int    $mode
     * @param  bool   $recursive
     * @param  array   $option
     * @return boolean
     */
    public function makeDirectory($dirname, $mode = 0777, $recursive = false, array $option = [])
    {
        if (is_array($mode)) {
            $option = $mode;

            $mode = 0777;
        }

        if (is_array($recursive)) {
            $option = $recursive;

            $recursive = false;
        }
    }

    /**
     * Recover the contents of the file
     *
     * @param  string $filename
     * @return null|string
     */
    public function get($filename)
    {
        // TODO
    }

    /**
     * Copy the contents of a source file to a target file.
     *
     * @param  string $target
     * @param  string $source
     * @return bool
     */
    public function copy($target, $source)
    {
        // TODO
    }

    /**
     * Renames or moves a source file to a target file.
     *
     * @param $target
     * @param $source
     */
    public function move($target, $source)
    {
        // TODO
    }

    /**
     * Check the existence of a file
     *
     * @param  $filename
     * @return bool
     */
    public function exists($filename)
    {
        // TODO
    }

    /**
     * The file extension
     *
     * @param  $filename
     * @return string
     */
    public function extension($filename)
    {
        // TODO
    }

    /**
     * isFile alias of is_file.
     *
     * @param  $filename
     * @return bool
     */
    public function isFile($filename)
    {
        // TODO
    }

    /**
     * isDirectory alias of is_dir.
     *
     * @param  $dirname
     * @return bool
     */
    public function isDirectory($dirname)
    {
        // TODO
    }

    /**
     * Resolves file path.
     * Give the absolute path of a path
     *
     * @param  $filename
     * @return string
     */
    public function path($filename)
    {
        // TODO
    }
}
