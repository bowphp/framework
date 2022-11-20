<?php

declare(strict_types=1);

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
    private static ?S3Service $instance = null;

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
    public function store(UploadFile $file, ?string $location = null, array $option = []): array|bool
    {
        $result = $this->put($file->getHashName(), $file->getContent());

        return $result["Location"];
    }

    /**
     * Add content after the contents of the file
     *
     * @param  string $file
     * @param  string $content
     * @return bool
     */
    public function append(string $filename, string $content): bool
    {
        $result = $this->get($filename);
        $new_content = $result . PHP_EOL . $content;
        $this->put($filename, $new_content);

        return isset($result["Location"]);
    }

    /**
     * Add content before the contents of the file
     *
     * @param  string $file
     * @param  string $content
     * @return bool
     * @throws
     */
    public function prepend(string $filename, string $content): bool
    {
        $result = $this->get($filename);
        $new_content = $content.PHP_EOL.$result;
        $this->put($filename, $new_content);

        return true;
    }

    /**
     * Put other file content in given file
     *
     * @param string $file
     * @param string $content
     * @param array $options
     *
     * @return bool
     */
    public function put(string $file, string $content, array $options = []): bool
    {
        $options = is_string($options)
            ? ['visibility' => $options]
            : (array) $options;

        $this->client->putObject([
            'Bucket' => $this->config['bucket'],
            'Key'    => $file,
            'Body'   => $content,
            "Visibility" => $options["visibility"] ?? 'public'
        ]);

        return true;
    }

    /**
     * Delete file or directory
     *
     * @param  string $filename
     * @return bool
     */
    public function delete(string|array $filename): bool
    {
        $paths = is_array($filename) ? $filename : func_get_args();

        $success = true;

        foreach ($paths as $path) {
            try {
                $this->client->deleteObject([
                    'Bucket' => $this->config['bucket'],
                    'Key' => $path
                ]);
            } catch (\Exception $e) {
                $success = false;
            }
        }

        return $success;
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

        return array_map(fn($file) => $file["Key"], $results["Contents"]);
    }

    /**
     * List the folder of a folder passed as a parameter
     *
     * @param  string $dirname
     * @return array
     */
    public function directories(string $dirname): array
    {
        $buckets = (array) $this->client->listBuckets();

        return array_map(fn($bucket) => $bucket["Name"], $buckets);
    }

    /**
     * Create a directory
     *
     * @param  string $bucket
     * @param  int    $mode
     * @param  bool   $recursive
     * @param  array  $option
     * @return bool
     */
    public function makeDirectory(string $bucket, int $mode = 0777, array $option = []): bool
    {
        $result = $this->client->createBucket([
            "Bucket" => $bucket
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
            'Key' => $filename
        ]);

        var_dump($result["Body"]);

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
        $result = $this->get($source);

        $this->put($target, $result["Body"]);

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
        $result = $this->get($dirname);

        return isset($result["Location"]);
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
        $result = $this->client->getObjectUrl($this->config["bucket"], $filename);

        return $result;
    }
}
