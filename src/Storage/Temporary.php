<?php

namespace Bow\Storage;

class Temporary
{
    /**
     * The temp buffer
     *
     * @var resource
     */
    private $stream;

    /**
     * Temporary Constructor
     *
     * @return void
     */
    public function __construct()
    {
        $this->open();
    }

    /**
     * Check if the streaming is open
     *
     * @return boolean
     */
    public function isOpen()
    {
        return is_resource($this->stream);
    }

    /**
     * Open the streaming
     *
     * @return void
     */
    public function open()
    {
        $this->stream = fopen('php://temp', 'w+b');
    }

    /**
     * Close the streaming
     *
     * @return void
     */
    public function close()
    {
        if ($this->isOpen()) {
            fclose($this->stream);
        }
    }

    /**
     * Write content
     *
     * @param string $content
     *
     * @return mixed
     */
    public function write($content)
    {
        if ($this->isOpen()) {
            return fwrite($this->stream, $content);
        }
    }

    /**
     * Read content of temp
     *
     * @return string|null
     */
    public function read()
    {
        if (!$this->isOpen()) {
            return null;
        }

        $content = fread($this->stream, 100000);

        return $content;
    }

    /**
     * Temporary destructor
     *
     * @return void
     */
    public function __destruct()
    {
        $this->close();
    }
}
