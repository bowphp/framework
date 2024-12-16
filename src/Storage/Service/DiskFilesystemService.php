<?php

declare(strict_types=1);

namespace Bow\Storage\Service;

use Bow\Http\UploadedFile;
use Bow\Storage\Contracts\FilesystemInterface;
use InvalidArgumentException;

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
     * @param  UploadedFile $file
     * @param  string|array $location
     * @param  array $option
     *
     * @return bool
     * @throws InvalidArgumentException
     */
    public function store(UploadedFile $file, string|array $location = null, array $option = []): bool
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

        return (bool) file_put_contents($file, $content);
    }

    /**
     * Add content after the contents of the file
     *
     * @param  string $file
     * @param  string $content
     *
     * @return bool
     */
    public function append(string $file, string $content): bool
    {
        return (bool) file_put_contents($file, $content, FILE_APPEND);
    }

    /**
     * Add content before the contents of the file
     *
     * @param  string $file
     * @param  string $content
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
     * Delete file or directory
     *
     * @param  string $file
     *
     * @return bool
     */
    public function delete(string $file): bool
    {
        $file = $this->path($file);

        if (!is_dir($file)) {
            if (is_file($file)) {
                return (bool) @unlink($file);
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

        return (bool) @rmdir($file);
    }

    /**
     * List the files of a folder passed as a parameter
     *
     * @param  string $dirname
     *
     * @return array
     */
    public function files(string $dirname): array
    {
        $dirname = $this->path($dirname);

        $directory_contents = glob($dirname . "/*");

        return array_filter($directory_contents, fn ($file) => filetype($file) == "file");
    }

    /**
     * List the folder of a folder passed as a parameter
     *
     * @param  string $dirname
     * @return array
     */
    public function directories(string $dirname): array
    {
        $directory_contents = glob($this->path($dirname) . "/*", GLOB_ONLYDIR);

        return $directory_contents;
    }

    /**
     * Create a directory
     *
     * @param  string $dirname
     * @param  int $mode
     * @return bool
     */
    public function makeDirectory(string $dirname, int $mode = 0777): bool
    {
        $result = @mkdir($dirname, $mode, true);

        return $result;
    }

    /**
     * Recover the contents of the file
     *
     * @param  string $filename
     *
     * @return int
     */
    public function get(string $filename): ?string
    {
        $filename = $this->path($filename);

        if (!(is_file($filename) && stream_is_local($filename))) {
            return null;
        }

        return file_get_contents($filename);
    }

    /**
     * Copy the contents of a source file to a target file.
     *
     * @param  string $target
     * @param  string $source
     *
     * @return bool
     */
    public function copy(string $target, string $source): bool
    {
        if (!$this->exists($target)) {
            throw new \RuntimeException("$target does not exist.", E_ERROR);
        }

        if (!$this->exists($source)) {
            $this->makeDirectory(dirname($source));
        }

        return (bool) file_put_contents($source, $this->get($target));
    }

    /**
     * Renames or moves a source file to a target file.
     *
     * @param string $target
     * @param string $source
     *
     * @return bool
     */
    public function move(string $target, string $source): bool
    {
        $this->copy($target, $source);

        $this->delete($target);

        return true;
    }

    /**
     * Check the existence of a file or directory
     *
     * @param string $filename
     * @return bool
     */
    public function exists(string $filename): bool
    {
        return $this->isFile($filename) || $this->isDirectory($filename);
    }

    /**
     * The file extension
     *
     * @param string $filename
     * @return string
     */
    public function extension(string $filename): ?string
    {
        if ($this->exists($filename)) {
            return pathinfo($this->path($filename), PATHINFO_EXTENSION);
        }

        return null;
    }

    /**
     * isFile alias of is_file.
     *
     * @param string $filename
     * @return bool
     */
    public function isFile(string $filename): bool
    {
        return is_file($this->path($filename));
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
     * Resolves file path.
     * Give the absolute path of a path
     *
     * @param string $filename
     * @return string
     */
    public function path(string $filename): string
    {
        if (preg_match('#^' . $this->base_directory . '#', $filename)) {
            return $filename;
        }

        return rtrim($this->base_directory, '/') . '/' . ltrim($filename, '/');
    }
}
