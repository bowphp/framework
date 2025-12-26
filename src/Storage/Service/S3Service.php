<?php

declare(strict_types=1);

namespace Bow\Storage\Service;

use Aws\S3\S3Client;
use Bow\Http\UploadedFile;
use Bow\Storage\Contracts\ServiceInterface;

class S3Service implements ServiceInterface
{
    /**
     * The S3Service instance
     *
     * @var ?S3Service
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
     * @param  array $config
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
     * @param  UploadedFile $file
     * @param  string|null  $location
     * @param  array        $option
     * @return array|bool|string
     */
    public function store(UploadedFile $file, ?string $location = null, array $option = []): array|bool|string
    {
        $putResult = $this->put($file->getHashName(), $file->getContent());

        return $putResult ? $this->path($file->getHashName()) : false;
    }

    /**
     * Put other file content in given file
     *
     * @param string $file
     * @param string $content
     * @param array  $options
     *
     * @return bool
     */
    public function put(string $file, string $content, array $options = []): bool
    {
        $options = is_string($options)
            ? ['visibility' => $options]
            : (array)$options;

        return (bool)$this->client->putObject([
            'Bucket' => $this->config['bucket'],
            'Key' => $file,
            'Body' => $content,
            "Visibility" => $options["visibility"] ?? 'public'
        ]);
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
        $result = $this->get($file);
        $new_content = $result . PHP_EOL . $content;
        $this->put($file, $new_content);

        return isset($result["Location"]);
    }

    /**
     * Recover the contents of the file
     *
     * @param  string $file
     * @return ?string
     */
    public function get(string $file): ?string
    {
        try {
            $this->client->headObject([
                'Bucket' => $this->config['bucket'],
                'Key' => $file
            ]);
        } catch (\Exception $e) {
            return null;
        }

        $result = $this->client->getObject([
            'Bucket' => $this->config['bucket'],
            'Key' => $file
        ]);

        if (isset($result["Body"])) {
            return $result["Body"]->getContents();
        }

        return null;
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
        $result = $this->get($file);
        $new_content = $content . PHP_EOL . $result;
        $this->put($file, $new_content);

        return true;
    }

    /**
     * List the files of a folder passed as a parameter
     *
     * @param  string $dirname
     * @return array
     */
    public function files(string $dirname = '/'): array
    {
        $result = $this->client->listObjectsV2([
            'Bucket' => $this->config['bucket'],
            'Prefix' => ltrim($dirname, '/'),
        ]);
        if (!isset($result['Contents'])) {
            return [];
        }
        return array_map(fn($file) => $file['Key'], $result['Contents']);
    }

    /**
     * List the folder of a folder passed as a parameter
     *
     * @param  string $dirname
     * @return array
     */
    public function directories(string $dirname): array
    {
        $result = $this->client->listObjectsV2([
            'Bucket' => $this->config['bucket'],
            'Delimiter' => '/',
            'Prefix' => ltrim($dirname, '/'),
        ]);
        if (!isset($result['CommonPrefixes'])) {
            return [];
        }
        return array_map(fn($prefix) => rtrim($prefix['Prefix'], '/'), $result['CommonPrefixes']);
    }

    /**
     * Create a directory
     *
     * @param  string $dirname
     * @param  int    $mode
     * @param  array  $option
     * @return bool
     */
    public function makeDirectory(string $dirname, int $mode = 0777, array $option = []): bool
    {
        // S3 does not have real directories, but we can create a placeholder object
        $result = $this->client->putObject([
            'Bucket' => $this->config['bucket'],
            'Key' => rtrim($dirname, '/') . '/',
            'Body' => '',
        ]);
        return isset($result['ObjectURL']) || isset($result['ETag']);
    }

    /**
     * Renames or moves a source file to a target file.
     *
     * @param  string $source
     * @param  string $target
     * @return bool
     */
    public function move(string $source, string $target): bool
    {
        $copied = $this->copy($source, $target);
        if ($copied) {
            return $this->delete($source);
        }
        return false;
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
        try {
            $this->client->copyObject([
                'Bucket' => $this->config['bucket'],
                'CopySource' => $this->config['bucket'] . '/' . $source,
                'Key' => $target,
            ]);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Delete file or directory
     *
     * @param  string $file
     * @return bool
     */
    public function delete(string $file): bool
    {
        return (bool) $this->client->deleteObject([
            'Bucket' => $this->config['bucket'],
            'Key' => $file
        ]);
    }

    /**
     * Check the existence of a file
     *
     * @param  string $file
     * @return bool
     */
    public function exists(string $file): bool
    {
        return (bool) $this->get($file);
    }

    /**
     * isFile alias of is_file.
     *
     * @param  string $file
     * @return bool
     */
    public function isFile(string $file): bool
    {
        $result = $this->get($file);
        return $result !== null && $result !== false;
    }

    /**
     * isDirectory alias of is_dir.
     *
     * @param  string $dirname
     * @return bool
     */
    public function isDirectory(string $dirname): bool
    {
        $result = $this->files($dirname);
        return is_array($result) && count($result) > 0;
    }

    /**
     * Resolves file path.
     * Give the absolute path of a path
     *
     * @param  string $file
     * @return string
     */
    public function path(string $file): string
    {
        return $this->client->getObjectUrl($this->config["bucket"], $file);
    }
}
