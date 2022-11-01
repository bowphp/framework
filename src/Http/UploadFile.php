<?php

declare(strict_types=1);

namespace Bow\Http;

class UploadFile
{
    /**
     * @var array
     */
    private $file;

    /**
     * UploadFile constructor.
     *
     * @param array $file
     */
    public function __construct(array $file)
    {
        $this->file = $file;
    }

    /**
     * Get the file extension
     *
     * @return string
     */
    public function getExtension()
    {
        if (!isset($this->file['name'])) {
            return null;
        }

        $extension = pathinfo(
            $this->file['name'],
            PATHINFO_EXTENSION
        );

        return strtolower($extension);
    }

    /**
     * The is `getExtension` alias
     *
     * @return string
     */
    public function extension()
    {
        return $this->getExtension();
    }

    /**
     * Get the file extension
     *
     * @return string
     */
    public function getTypeMime()
    {
        if (isset($this->file['type'])) {
            return $this->file['type'];
        }

        return null;
    }

    /**
     * Get the size of the file
     *
     * @return mixed
     */
    public function getFilesize()
    {
        if (isset($this->file['size'])) {
            return $this->file['size'];
        }

        return null;
    }

    /**
     * Check if the file is uploader
     *
     * @return bool
     */
    public function isUploaded()
    {
        if (!isset($this->file['tmp_name'], $this->file['error'])) {
            return false;
        }

        return is_uploaded_file($this->file['tmp_name']) && $this->file['error'] === UPLOAD_ERR_OK;
    }

    /**
     * Get the main name of the file
     *
     * @return string
     */
    public function getBasename()
    {
        if (!isset($this->file['name'])) {
            return null;
        }

        return basename($this->file['name']);
    }

    /**
     * Get the filename
     *
     * @return mixed
     */
    public function getFilename()
    {
        if (!isset($this->file['name'])) {
            return null;
        }

        return $this->file['name'];
    }

    /**
     * Get the file content
     *
     * @return string
     */
    public function getContent()
    {
        if (!isset($this->file['tmp_name'])) {
            return null;
        }

        return file_get_contents($this->file['tmp_name']);
    }

    /**
     * Get the file hash name
     *
     * @return string
     */
    public function getHashName()
    {
        return strtolower(hash('sha256', $this->getBasename())).'.'.$this->getExtension();
    }

    /**
     * Move the uploader file to a directory.
     *
     * @param  string $to
     * @param  string|null $filename
     * @return bool
     * @throws
     */
    public function moveTo($to, $filename = null)
    {
        if (!isset($this->file['tmp_name'])) {
            return false;
        }

        if (is_null($filename)) {
            $filename = $this->getHashName();
        }

        if (!is_dir($to)) {
            @mkdir($to, 0777, true);
        }

        $resolve = rtrim($to, '/').'/'.$filename;

        return (bool) move_uploaded_file($this->file['tmp_name'], $resolve);
    }
}
