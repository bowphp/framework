<?php

namespace Bow\Storage;

use Bow\Storage\Exception\ResourceException;

class Temporary
{
    /**
     * The temp buffer
     *
     * @var resource
     */
    private $stream;

    /**
     * The Lock filename
     *
     * @var string
     */
    private $lock_filename;

    /**
     * Temporary Constructor
     *
     * @param string $lock_filename
     *
     * @return void
     */
    public function __construct($lock_filename = 'php://temp')
    {
        $this->lock_filename = $lock_filename;

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
        if (is_resource($this->stream)) {
            throw new ResourceException(
                'The temporary file is already open.'
            );
        }

        $this->stream = fopen($this->lock_filename, 'w+b');
    }

    /**
     * Set the Lock file name
     *
     * @param string $lock_filename
     *
     * @return void
     */
    public function lockFile($lock_filename)
    {
        $this->close();

        $this->lock_filename = $lock_filename;
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
        if (!$this->isOpen()) {
            $this->open();
        }

        return fwrite($this->stream, $content);
    }

    /**
     * Read content of temp
     *
     * @return string|null
     */
    public function read()
    {
        if (!$this->isOpen()) {
            $this->open();
        }

        $this->stream = fopen($this->lock_filename, 'r');

        $content = stream_get_contents($this->stream);

        $this->close();

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
