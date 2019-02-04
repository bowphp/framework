<?php

namespace Bow\Storage\Service;

use Bow\Http\UploadFile;
use Bow\Storage\Contracts\ServiceInterface;
use Bow\Storage\Exception\ResourceException;
use InvalidArgumentException;
use RuntimeException;

class FTPService implements ServiceInterface
{
    /**
     * The Service configuration
     *
     * @var array
     */
    private $config = [];

    /**
     * Ftp connection
     *
     * @var resource
     */
    private $connection;

    /**
     * Transfer mode
     *
     * @var int
     */
    private $transfer_mode = FTP_BINARY;

    /**
     * Whether to use the passive mode.
     *
     * @var bool
     */
    private $use_passive_mode = true;

    /**
     * Root folder absolute path.
     *
     * @var string
     */
    private $root;

    /**
     * The FTPService Instance
     *
     * @var FTPService
     */
    private static $instance;

    /**
     * FTPService constructor
     *
     * @param array $config
     * @return void
     */
    private function __construct(array $config)
    {
        $this->config = $config;

        $this->connect();
    }

    /**
     * Configure service
     *
     * @param array $config
     *
     * @return FTPService
     */
    public static function configure(array $config)
    {
        if (is_null(static::$instance)) {
            static::$instance = new static($config);
        }

        return static::$instance;
    }

    /**
     * Connect to the FTP server.
     *
     * @return void
     */
    public function connect()
    {
        ['hostname' => $host, 'port' => $port, 'timeout' => $timeout] = $this->config;

        if ($this->config['tls']) {
            $this->connection = ftp_ssl_connect($host, $port, $timeout);
        } else {
            $this->connection = ftp_connect($host, $port, $timeout);
        }

        if (!$this->connection) {
            throw new RuntimeException(sprintf('Could not connect to %s:%s', $host, $port));
        }

        $this->login();
        $this->setConnectionRoot();
        $this->setConnectionPassiveMode();
    }

    /**
     * Disconnect from the FTP server.
     *
     * @return void
     */
    public function disconnect()
    {
        if (is_resource($this->connection)) {
            ftp_close($this->connection);
        }

        $this->connection = null;
    }

    /**
     * Make FTP Login.
     *
     * @return void
     * @throws RuntimeException
     */
    private function login()
    {
        ['username' => $username, 'password' => $password] = $this->config;

        // Disable error handling to avoid credentials leak
        set_error_handler(function () {
        });
        $is_logged_in = ftp_login($this->connection, $username, $password);
        restore_error_handler();

        if (!$is_logged_in) {
            $this->disconnect();
            
            throw new RuntimeException(
                sprintf(
                    'Could not login with connection: (s)ftp://%s@%s:%s',
                    $username,
                    $this->config['hostname'],
                    $this->config['port']
                )
            );
        }
    }

    /**
     * Set the connection root.
     *
     * @return void
     */
    private function setConnectionRoot()
    {
        ['root' => $root] = $this->config;

        if ($root && (!ftp_chdir($this->connection, $root))) {
            throw new RuntimeException('Root is invalid or does not exist: ' . $root);
        }

        // Store absolute path for further reference.
        // This is needed when creating directories and
        // initial root was a relative path, else the root
        // would be relative to the chdir'd path.
        $this->root = ftp_pwd($this->connection);
    }

    /**
     * Get ftp connextion
     *
     * @return mixed
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Return the current working directory.
     *
     * @return mixed
     */
    public function getCurrentDirectory()
    {
        return pathinfo(ftp_pwd($this->connection), PATHINFO_BASENAME);
    }

    /**
     * Store directly the upload file
     *
     * @param  UploadFile $file
     * @param  string $location
     * @param  array $option
     * @return mixed
     * @throws InvalidArgumentException
     */
    public function store(UploadFile $file, $location = null, array $option = [])
    {
        $content = $file->getContent();
        $stream = fopen('php://temp', 'w+b');

        fwrite($stream, $content);
        rewind($stream);

        $result = $this->writeStream($location, $stream, $option);
        fclose($stream);

        if ($result === false) {
            return false;
        }

        $result['content'] = $content;

        return $result;
    }

    /**
     * Write following a file specify
     *
     * @param  string $file
     * @param  string $content
     * @return bool
     */
    public function append($file, $content)
    {
        // TODO: Implement append() method.
    }

    /**
     * Write to the beginning of a file specify
     *
     * @param  string $file
     * @param  string $content
     * @return bool
     * @throws
     */
    public function prepend($file, $content)
    {
        // TODO: Implement prepend() method.
    }

    /**
     * Put other file content in given file
     *
     * @param  string $file
     * @param  string $content
     * @return bool
     */
    public function put($file, $content)
    {
        // TODO: Implement put() method.
    }

    /**
     * Alias sur readInDir
     *
     * @param  string $dirname
     * @return array
     */
    public function files($dirname)
    {
        // TODO: Implement files() method.
    }

    /**
     * Read the contents of the file
     *
     * @param  string $dirname
     * @return array
     */
    public function directories($dirname)
    {
        // TODO: Implement directories() method.
    }

    /**
     * Create a directory
     *
     * @param  string $dirname
     * @param  int $mode
     * @param  bool $recursive
     * @return boolean
     */
    public function makeDirectory($dirname, $mode = 0777, $recursive = false)
    {
        // TODO: Implement makeDirectory() method.
    }

    /**
     * Get file content
     *
     * @param  string $filename
     * @return null|string
     */
    public function get($filename)
    {
        if (!$stream = $this->readStream($filename)) {
            return false;
        }

        $contents = stream_get_contents($stream);
        fclose($stream);

        return $contents;
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
       $source_stream = $this->readStream($source);
       $result = $this->writeStream($target, $source_stream);

       fclose($source_stream);

       return $result;
    }

    /**
     * Rename or move a source file to a target file.
     *
     * @param string $target
     * @param string $source
     * @return bool
     */
    public function move($target, $source)
    {
        return ftp_rename($this->getConnection(), $target, $source);
    }

    /**
     * Check the existence of a file
     *
     * @param string $filename
     * @return bool
     */
    public function exists($filename)
    {
        // TODO: Implement exists() method.
    }

    /**
     * The file extension
     *
     * @param string $filename
     * @return string
     */
    public function extension($filename)
    {
        // TODO: Implement extension() method.
    }

    /**
     * isFile alias of is_file.
     *
     * @param string $filename
     * @return bool
     */
    public function isFile($filename)
    {
        // TODO: Implement isFile() method.
    }

    /**
     * isDirectory alias of is_dir.
     *
     * @param string $dirname
     * @return bool
     */
    public function isDirectory($dirname)
    {
        // TODO: Implement isDirectory() method.
    }

    /**
     * Resolves a path.
     * Give the absolute path of a path
     *
     * @param string $filename
     * @return string
     */
    public function path($filename)
    {
        // TODO: Implement path() method.
    }


    /**
     * Delete file
     *
     * @param  string $file
     * @return bool
     */
    public function delete($file)
    {
        return ftp_delete($this->getConnection(), $file);
    }

    /**
     * Write stream
     *
     * @param string $path
     * @param resouce $resource
     *
     * @return array|boolean
     */
    private function writeStream($path, $resource)
    {
        if (!ftp_fput($this->getConnection(), $path, $resource, $this->transfer_mode)) {
            return false;
        }

        $type = 'file';

        return compact('type', 'path');
    }

    /**
     * Read stream
     *
     * @param string $path
     *
     * @return mixed
     */
    private function readStream($path)
    {
        try {
            $stream = fopen('php://temp', 'w+b');
            $result = ftp_fget($this->getConnection(), $stream, $path, $this->transfer_mode);
            rewind($stream);

            if ($result) {
                return $stream;
            }

            fclose($stream);
            
            return false;
        } catch (\Exception $exception) {
            throw new ResourceException(sprintf('"%s" not found.', $path));
        }
    }

    /**
     * Set the connections to passive mode.
     *
     * @throws RuntimeException
     */
    private function setConnectionPassiveMode()
    {
        if (!ftp_pasv($this->connection, $this->use_passive_mode)) {
            throw new RuntimeException(
                'Could not set passive mode for connection: '
                . $this->config['hostname'] . '::' . $this->config['port']
            );
        }
    }
}
