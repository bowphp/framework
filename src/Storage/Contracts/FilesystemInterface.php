<?php

namespace Bow\Storage\Contracts;

use Bow\Http\UploadFile;
use InvalidArgumentException;

interface FilesystemInterface
{
    /**
     * Store directly the upload file
     *
     * @param  UploadFile $file
     * @param  string  $location
     * @param  array $option
     * @return bool
     * @throws InvalidArgumentException
     */
    public function store(UploadFile $file, ?string $location = null, array $option = []): array|bool;

    /**
     * Write following a file specify
     *
     * @param  string $file
     * @param  string $content
     * @return bool
     */
    public function append(string $file, string $content): bool;

    /**
     * Write to the beginning of a file specify
     *
     * @param  string $file
     * @param  string $content
     * @return bool
     * @throws
     */
    public function prepend(string $file, string $content);

    /**
     * Put other file content in given file
     *
     * @param  string $file
     * @param  string $content
     * @return bool
     */
    public function put(string $file, string $content);

    /**
     * Delete file
     *
     * @param  string $file
     * @return bool
     */
    public function delete(string $file): bool;

    /**
     * Alias sur readInDir
     *
     * @param  string $dirname
     * @return array
     */
    public function files(string $dirname): array;

    /**
     * Read the contents of the file
     *
     * @param  string $dirname
     * @return array
     */
    public function directories(string $dirname): array;

    /**
     * Create a directory
     *
     * @param  string $dirname
     * @param  int    $mode
     * @return bool
     */
    public function makeDirectory(string $dirname, int $mode = 0777): bool;

    /**
     * Get file content
     *
     * @param  string $filename
     * @return ?string
     */
    public function get(string $filename): ?string;

    /**
     * Copy the contents of a source file to a target file.
     *
     * @param  string $target
     * @param  string $source
     * @return bool
     */
    public function copy(string $target, string $source): bool;

    /**
     * Rénme or move a source file to a target file.
     *
     * @param string $target
     * @param string $source
     * @return bool
     */
    public function move(string $target, string $source): bool;

    /**
     * Check the existence of a file
     *
     * @param string $filename
     * @return bool
     */
    public function exists(string $filename): bool;

    /**
     * isFile alias of is_file.
     *
     * @param string $filename
     * @return bool
     */
    public function isFile(string $filename): bool;

    /**
     * isDirectory alias of is_dir.
     *
     * @param string $dirname
     * @return bool
     */
    public function isDirectory(string $dirname): bool;

    /**
     * Resolves a path.
     * Give the absolute path of a path
     *
     * @param string $filename
     * @return string
     */
    public function path(string $filename): string;
}
