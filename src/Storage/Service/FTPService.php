<?php

namespace Bow\Storage\Service;

use Bow\Http\UploadFile;
use Bow\Storage\Contracts\ServiceInterface;
use InvalidArgumentException;
use RuntimeException;

class FTPService implements ServiceInterface
{

    /**
     * @var array
     */
    protected static $config = [];

    /**
     * Ftp connection
     *
     */
    protected static $connection;

    /**
     * Root folder absolute path.
     *
     * @var string
     */
    protected static $root;

    public function __construct()
    {
        $this->connect();
    }

    /**
     * Configure service
     *
     * @param array $config
     * @return FTPService
     */
    public static function configure(array $config)
    {
        static::$config = $config;
        return new static();
    }

    /**
     * Connect to the FTP server.
     */
    public function connect()
    {
        ['hostname' => $host, 'port' => $port, 'timeout' => $timeout] = self::$config;

        if (self::$config['tls']) {
            self::$connection = ftp_ssl_connect($host, $port, $timeout);
        } else {
            self::$connection = ftp_connect($host, $port, $timeout);
        }

        if (!self::$connection) {
            throw new RuntimeException('Could not connect to host: ' . $host . ', port:' . $port);
        }

        $this->login();
        $this->setConnectionRoot();
    }

    /**
     * Disconnect from the FTP server.
     */
    public function disconnect()
    {
        if (is_resource($this->connection)) {
            ftp_close($this->connection);
        }
        $this->connection = null;
    }

    /**
     * Login.
     *
     * @throws RuntimeException
     */
    protected function login()
    {
        ['username' => $username, 'password' => $password] = self::$config;
        // Disable error handling to avoid credentials leak
        set_error_handler(function () {
        });
        $isLoggedIn = ftp_login(self::$connection, $username, $password);
        restore_error_handler();

        if (!$isLoggedIn) {
            $this->disconnect();
            throw new RuntimeException(
                'Could not login with connection: '
                . self::$config['hostname'] . '::' . self::$config['port']
                . ', username: ' . $username
            );
        }
    }

    /**
     * Set the connection root.
     */
    protected function setConnectionRoot()
    {
        ['root' => $root] = self::$config;

        if ($root && (!ftp_chdir(self::$connection, $root))) {
            throw new RuntimeException('Root is invalid or does not exist: ' . $root);
        }

        // Store absolute path for further reference.
        // This is needed when creating directories and
        // initial root was a relative path, else the root
        // would be relative to the chdir'd path.
        self::$root = ftp_pwd(self::$connection);
    }

    /**
     * @return mixed
     */
    public static function getConnection()
    {
        return self::$connection;
    }

    /**
     * Return the current working directory.
     *
     * @return mixed
     */
    public function getCurrentDir()
    {
        return pathinfo(ftp_pwd(self::$connection), PATHINFO_BASENAME);
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
        // TODO: Implement store() method.
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
        // TODO: Implement get() method.
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
        // TODO: Implement copy() method.
    }

    /**
     * Rénme or move a source file to a target file.
     *
     * @param string $target
     * @param string $source
     */
    public function move($target, $source)
    {
        // TODO: Implement move() method.
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
     * @return boolean
     */
    public function delete($file)
    {
        // TODO: Implement delete() method.
    }
}
