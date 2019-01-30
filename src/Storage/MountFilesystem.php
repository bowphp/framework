<?php

namespace Bow\Storage;

use Bow\Http\UploadFile;
use Bow\Storage\Contracts\FilesystemInterface;
use InvalidArgumentException;

class MountFilesystem implements FilesystemInterface
{
    /**
     * The base work directory
     *
     * @var string
     */
    private $basedir;

    /**
     * MountFilesystem constructor.
     *
     * @param string $basedir
     */
    public function __construct($basedir)
    {
        $this->basedir = realpath($basedir);
    }

    /**
     * Function to upload a file
     *
     * @param  UploadFile  $file
     * @param  string  $location
     * @param  array   $option
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function store(UploadFile $file, $location = null, array $option = [])
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
            $location = trim($location, '/').'/'.$filename;
        }

        $this->put($location, $file->getContent());
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
        return file_put_contents($file, $content, FILE_APPEND);
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
        $tmp_content = file_get_contents($file);

        $this->put($file, $content);

        return $this->append($file, $tmp_content);
    }

    /**
     * Put other file content in given file
     *
     * @param string $file
     * @param string $content
     * @return bool
     */
    public function put($file, $content)
    {
        $file = $this->path($file);

        $dirname = dirname($file);

        $this->makeDirectory($dirname);

        return file_put_contents($file, $content);
    }

    /**
     * Delete file or directory
     *
     * @param  string $file
     * @return boolean
     */
    public function delete($file)
    {
        $file = $this->path($file);

        if (is_dir($file)) {
            return @rmdir($file);
        }

        return @unlink($file);
    }

    /**
     * List the files of a folder passed as a parameter
     *
     * @param  string $dirname
     * @return array
     */
    public function files($dirname)
    {
        $dirname = $this->path($dirname);

        $directory_contents = glob($dirname."/*");

        return array_filter($directory_contents, function ($file) {
            return filetype($file) == "file";
        });
    }

    /**
     * List the folder of a folder passed as a parameter
     *
     * @param  string $dirname
     * @return array
     */
    public function directories($dirname)
    {
        $directory_contents = glob($this->path($dirname)."/*");

        return array_filter($directory_contents, function ($file) {
            return filetype($file) == "dir";
        });
    }

    /**
     * Create a directory
     *
     * @param  string $dirname
     * @param  int    $mode
     * @param  bool   $recursive
     * @return boolean
     */
    public function makeDirectory($dirname, $mode = 0777, $recursive = false)
    {
        if (is_bool($mode)) {
            $recursive = $mode;

            $mode = 0777;
        }

        $dirname = $this->path($dirname);

        if (!is_dir($dirname)) {
            return @mkdir($dirname, $mode, $recursive);
        }

        return false;
    }

    /**
     * Recover the contents of the file
     *
     * @param  string $filename
     * @return null|string
     */
    public function get($filename)
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
     * @return bool
     */
    public function copy($target, $source)
    {
        if (!$this->exists($target)) {
            throw new \RuntimeException("$target does not exist.", E_ERROR);
        }

        if (!$this->exists($source)) {
            $this->makeDirectory(dirname($source), true);
        }

        return file_put_contents($source, $this->get($target));
    }

    /**
     * Renames or moves a source file to a target file.
     *
     * @param $target
     * @param $source
     */
    public function move($target, $source)
    {
        $this->copy($target, $source);

        $this->delete($target);
    }

    /**
     * Check the existence of a file
     *
     * @param  $filename
     * @return bool
     */
    public function exists($filename)
    {
        $filename = $this->path($filename);

        if (! $this->isDirectory($filename)) {
            return file_exists($filename);
        }
    
        $tmp = getcwd();

        $r = chdir($filename);

        chdir($tmp);

        return $r;
    }

    /**
     * The file extension
     *
     * @param  $filename
     * @return string
     */
    public function extension($filename)
    {
        if ($this->exists($filename)) {
            return pathinfo($this->path($filename), PATHINFO_EXTENSION);
        }

        return null;
    }

    /**
     * isFile alias of is_file.
     *
     * @param  $filename
     * @return bool
     */
    public function isFile($filename)
    {
        return is_file($this->path($filename));
    }

    /**
     * isDirectory alias of is_dir.
     *
     * @param  $dirname
     * @return bool
     */
    public function isDirectory($dirname)
    {
        return is_dir($this->path($dirname));
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
        if (preg_match('~^'.$this->basedir.'~', $filename)) {
            return $filename;
        }

        return rtrim($this->basedir, '/').'/'.ltrim($filename, '/');
    }
}
