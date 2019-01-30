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
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function store(UploadFile $file, $location = null, array $option = []);

    /**
     * Write following a file specify
     *
     * @param  string $file
     * @param  string $content
     * @return bool
     */
    public function append($file, $content);

    /**
     * Write to the beginning of a file specify
     *
     * @param  string $file
     * @param  string $content
     * @return bool
     * @throws
     */
    public function prepend($file, $content);

    /**
     * Put other file content in given file
     *
     * @param  string $file
     * @param  string $content
     * @return bool
     */
    public function put($file, $content);

    /**
     * Delete file
     *
     * @param  string $file
     * @return boolean
     */
    public function delete($file);

    /**
     * Alias sur readInDir
     *
     * @param  string $dirname
     * @return array
     */
    public function files($dirname);

    /**
     * Read the contents of the file
     *
     * @param  string $dirname
     * @return array
     */
    public function directories($dirname);

    /**
     * Create a directory
     *
     * @param  string $dirname
     * @param  int    $mode
     * @param  bool   $recursive
     * @return boolean
     */
    public function makeDirectory($dirname, $mode = 0777, $recursive = false);

    /**
     * Get file content
     *
     * @param  string $filename
     * @return null|string
     */
    public function get($filename);

    /**
     * Copy the contents of a source file to a target file.
     *
     * @param  string $target
     * @param  string $source
     * @return bool
     */
    public function copy($target, $source);

    /**
     * Rénme or move a source file to a target file.
     *
     * @param string $target
     * @param string $source
     */
    public function move($target, $source);

    /**
     * Check the existence of a file
     *
     * @param string $filename
     * @return bool
     */
    public function exists($filename);

    /**
     * The file extension
     *
     * @param string $filename
     * @return string
     */
    public function extension($filename);

    /**
     * isFile alias of is_file.
     *
     * @param string $filename
     * @return bool
     */
    public function isFile($filename);

    /**
     * isDirectory alias of is_dir.
     *
     * @param string $dirname
     * @return bool
     */
    public function isDirectory($dirname);

    /**
     * Resolves a path.
     * Give the absolute path of a path
     *
     * @param string $filename
     * @return string
     */
    public function path($filename);
}
