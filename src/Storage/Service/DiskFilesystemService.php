<?php

declare(strict_types=1);

namespace Bow\Storage\Service;

use Bow\Http\UploadedFile;
use Bow\Storage\Contracts\FilesystemInterface;
use RuntimeException;

class DiskFilesystemService implements FilesystemInterface
{
    /**
     * The base work directory
     *
     * @var string
     */
    private string $base_directory;

    /**
     * The current working directory
     *
     * @var string
     */
    private string $current_working_dir;

    /**
     * MountFilesystem constructor.
     *
     * @param string $base_directory
     */
    public function __construct(string $base_directory)
    {
        $this->base_directory = realpath($base_directory);
        $this->current_working_dir = $this->base_directory;

        // Set the root folder
        chdir($this->base_directory);
    }

    /**
     * Get the base directory
     *
     * @return string
     */
    public function getBaseDirectory(): string
    {
        return $this->base_directory;
    }

    /**
     * Function to upload a file
     *
     * @param UploadedFile $file
     * @param string|array|null $location
     * @param array $option
     *
     * @return array|bool|string
     */
    public function store(UploadedFile $file, string|array $location = null, array $option = []): array|bool|string
    {
        if (is_array($location)) {
            $option = $location;
            $location = null;
        }

        if (isset($option['as'])) {
            $filename = $option['as'];
        } else {
            $filename = $file->getHashName();
        }

        if (is_null($location)) {
            $location = $filename;
        } else {
            $location = trim($location, '/') . '/' . $filename;
        }

        return $this->put($location, $file->getContent());
    }

    /**
     * Put other file content in given file
     *
     * @param string $file
     * @param string $content
     *
     * @return bool
     */
    public function put(string $file, string $content): bool
    {
        $file = $this->path($file);

        $dirname = dirname($file);

        // We try to create the directory
        $this->makeDirectory($dirname);

        return (bool)file_put_contents($file, $content);
    }

    /**
     * Resolves file path.
     * Give the absolute path of a path
     *
     * @param string $file
     * @return string
     */
    public function path(string $file): string
    {
        if (preg_match('#^' . $this->base_directory . '#', $file)) {
            return $file;
        }

        return rtrim($this->base_directory, '/') . '/' . ltrim($file, '/');
    }

    /**
     * Create a directory
     *
     * @param string $dirname
     * @param int $mode
     * @return bool
     */
    public function makeDirectory(string $dirname, int $mode = 0777): bool
    {
        return @mkdir($dirname, $mode, true);
    }

    /**
     * Add content before the contents of the file
     *
     * @param string $file
     * @param string $content
     *
     * @return bool
     * @throws
     */
    public function prepend(string $file, string $content): bool
    {
        $tmp_content = file_get_contents($file);

        $this->put($file, $content);

        return $this->append($file, $tmp_content);
    }

    /**
     * Add content after the contents of the file
     *
     * @param string $file
     * @param string $content
     *
     * @return bool
     */
    public function append(string $file, string $content): bool
    {
        return (bool)file_put_contents($file, $content, FILE_APPEND);
    }

    /**
     * List the files of a folder passed as a parameter
     *
     * @param string $dirname
     *
     * @return array
     */
    public function files(string $dirname): array
    {
        $dirname = $this->path($dirname);

        $directory_contents = glob($dirname . "/*");

        return array_filter($directory_contents, fn($file) => filetype($file) == "file");
    }

    /**
     * List the folder of a folder passed as a parameter
     *
     * @param string $dirname
     * @return array
     */
    public function directories(string $dirname): array
    {
        return glob($this->path($dirname) . "/*", GLOB_ONLYDIR);
    }

    /**
     * Renames or moves a source file to a target file.
     *
     * @param string $source
     * @param string $target
     * @return bool
     */
    public function move(string $source, string $target): bool
    {
        $this->copy($source, $target);

        $this->delete($source);

        return true;
    }

    /**
     * Copy the contents of a source file to a target file.
     *
     * @param string $source
     * @param string $target
     * @return bool
     */
    public function copy(string $source, string $target): bool
    {
        if (!$this->exists($source)) {
            throw new RuntimeException("$source does not exist.", E_ERROR);
        }

        if (!$this->exists($target)) {
            $this->makeDirectory(dirname($target));
        }

        return (bool)file_put_contents($target, $this->get($source));
    }

    /**
     * Check the existence of a file or directory
     *
     * @param string $file
     * @return bool
     */
    public function exists(string $file): bool
    {
        return $this->isFile($file) || $this->isDirectory($file);
    }

    /**
     * isFile alias of is_file.
     *
     * @param string $file
     * @return bool
     */
    public function isFile(string $file): bool
    {
        return is_file($this->path($file));
    }

    /**
     * isDirectory alias of is_dir.
     *
     * @param string $dirname
     * @return bool
     */
    public function isDirectory(string $dirname): bool
    {
        return is_dir($this->path($dirname));
    }

    /**
     * Recover the contents of the file
     *
     * @param string $file
     * @return string|null
     */
    public function get(string $file): ?string
    {
        $file = $this->path($file);

        if (!(is_file($file) && stream_is_local($file))) {
            return null;
        }

        return file_get_contents($file);
    }

    /**
     * Delete file or directory
     *
     * @param string $file
     *
     * @return bool
     */
    public function delete(string $file): bool
    {
        $file = $this->path($file);

        if (!is_dir($file)) {
            if (is_file($file)) {
                return (bool)@unlink($file);
            }
        }

        $files = glob($file . "/*", GLOB_MARK);

        foreach ($files as $file) {
            if (is_dir($file)) {
                $this->delete($file);
            } else {
                @unlink($file);
            }
        }

        return (bool)@rmdir($file);
    }

    /**
     * The file extension
     *
     * @param string $filename
     * @return string|null
     */
    public function extension(string $filename): ?string
    {
        if ($this->exists($filename)) {
            return pathinfo($this->path($filename), PATHINFO_EXTENSION);
        }

        return null;
    }
}
