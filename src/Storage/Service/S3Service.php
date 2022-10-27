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
    private static S3Service $instance;

    /**
     * The attribute define the service configuration
     *
     * @var array
     */
    private array $config;

    /**
     * The attribute define the guzzle http configuration
     *
     * @var S3Client
     */
    private S3Client $client;

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
    public static function configure(array $config): S3Service
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
    public static function getInstance(): S3Service
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
    public function store(UploadFile $file, string $location = null, array $option = [])
    {
        $result = $this->get($filename);

        return $result["Location"];
    }

    /**
     * Add content after the contents of the file
     *
     * @param  string $file
     * @param  string $content
     * @return bool
     */
    public function append(string $file, string $content): bool
    {
        $result = $this->get($filename);

        return $result["Location"];
    }

    /**
     * Add content before the contents of the file
     *
     * @param  string $file
     * @param  string $content
     * @return bool
     * @throws
     */
    public function prepend(string $file, string $content): bool
    {
        $result = $this->get($filename);

        return $result["Location"];
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
    public function put(string $file, string $content, array $option = []): bool
    {
        if (isset($option['bucket'])) {
            $bucket = $option['bucket'];
        } else {
            $bucket = $this->config['bucket'];
        }

        $this->client->putObject([
            'Bucket' => $bucket,
            'Key'    => $file,
            'Body'   => $content
        ]);
    }

    /**
     * Delete file or directory
     *
     * @param  string $filename
     * @return bool
     */
    public function delete(string $filename): bool
    {
        $result = $this->client->deleteObject([
            'Bucket' => $this->config['bucket'],
            'Key' => $filename
        ]);

        return $result["DeleteMarker"];
    }

    /**
     * List the files of a folder passed as a parameter
     *
     * @param  string $dirname
     * @return array
     */
    public function files(string $dirname): array
    {
        $results = $this->client->listObjects([
            "Bucket" => $dirname
        ]);

        return array_map(fn($file) => $file["Key"], $result["Contents"]);
    }

    /**
     * List the folder of a folder passed as a parameter
     *
     * @param  string $dirname
     * @return array
     */
    public function directories(string $dirname): array
    {
        $buckets = $this->client->listBuckets();

        return array_map(fn($bucket) => $bucket["Name"], $buckets);
    }

    /**
     * Create a directory
     *
     * @param  string $dirname
     * @param  int    $mode
     * @param  bool   $recursive
     * @param  array  $option
     * @return bool
     */
    public function makeDirectory(string $dirname, array $option = []): bool
    {
        $result = $this->client->createBucket([
            "Bucket" => $dirname
        ]);

        return isset($result["Location"]);
    }

    /**
     * Recover the contents of the file
     *
     * @param  string $filename
     * @return null|string
     */
    public function get(string $filename): string
    {
        $result = $this->client->getObject([
            'Bucket' => $this->config['bucket'],
            'Key'    => $filename
        ]);

        return $result["Body"];
    }

    /**
     * Copy the contents of a source file to a target file.
     *
     * @param  string $source
     * @param  string $target
     * @return bool
     */
    public function copy(string $source, string $target): bool
    {
        $result = $this->client->getObject([
            'Bucket' => $this->config['bucket'],
            'Key'    => $source
        ]);

        $this->put($target, $result);

        return true;
    }

    /**
     * Renames or moves a source file to a target file.
     *
     * @param $source
     * @param $target
     */
    public function move(string $source, string $target): bool
    {
        $this->copy($source, $target);

        $this->delete($source);

        return true;
    }

    /**
     * Check the existence of a file
     *
     * @param  $filename
     * @return bool
     */
    public function exists(string $filename): bool
    {
        $result = (bool) $this->get($filename);

        return $result;
    }

    /**
     * isFile alias of is_file.
     *
     * @param  $filename
     * @return bool
     */
    public function isFile(string $filename): bool
    {
        $result = $this->get($filename);

        return strlen($result) > -1;
    }

    /**
     * isDirectory alias of is_dir.
     *
     * @param  $dirname
     * @return bool
     */
    public function isDirectory(string $dirname): bool
    {
        $result = $this->get($filename);

        return $result["Location"];
    }

    /**
     * Resolves file path.
     * Give the absolute path of a path
     *
     * @param  $filename
     * @return string
     */
    public function path(string $filename): string
    {
        $result = $this->client->getObject([
            "Bucket" => $this->config["bucket"],
            "Key" => $filename
        ]);

        return $result["Location"];
    }
}
