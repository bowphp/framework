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
    private $config;

    /**
     * Ftp connection
     *
     * @var \FTP\Connection
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
    private $base_directory;

    /**
     * The FTPService Instance
     *
     * @var FTPService
     */
    private static $instance;

    /**
     * Cache the directory contents to avoid redundant server calls.
     */
    private static $cached_directory_contents = [];

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
     * @return FTPService
     */
    public static function configure(array $config)
    {
        if (is_null(static::$instance)) {
            static::$instance = new FTPService($config);
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
        $host = $this->config['hostname'];
        $port = $this->config['port'];
        $timeout = $this->config['timeout'];

        if ($this->config['tls']) {
            $this->connection = ftp_ssl_connect($host, $port, $timeout);
        } else {
            $this->connection = ftp_connect($host, $port, $timeout);
        }

        if (!$this->connection) {
            throw new RuntimeException(
                sprintf('Could not connect to %s:%s', $host, $port)
            );
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

        // We disable error handling to avoid credentials leak :+1:
        set_error_handler(
            function () {
            }
        );

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
     * @param string $path
     * @return void
     */
    public function setConnectionRoot($path = '')
    {
        $base_path = $path ?: $this->config['root'];

        if ($base_path && (!ftp_chdir($this->connection, $base_path))) {
            throw new RuntimeException('Root is invalid or does not exist: ' . $base_path);
        }

        // Store absolute path for further reference.
        $this->base_directory = ftp_pwd($this->connection);
    }

    /**
     * Get ftp connextion
     *
     * @return \FTP\Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Return the current working directory.
     *
     * @return string
     */
    public function getCurrentDirectory()
    {
        $path = pathinfo(ftp_pwd($this->connection));

        return $path['basename'];
    }

    /**
     * Store directly the upload file
     *
     * @param  UploadFile $file
     * @param  string $location
     * @param  array $option
     *
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
     * Append content a file.
     *
     * @param  string $file
     * @param  string $content
     * @return bool
     */
    public function append($file, $content)
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        // prevent ftp_fput from seeking local "file" ($h)
        ftp_set_option($this->getConnection(), FTP_AUTOSEEK, false);

        $size = ftp_size($this->getConnection(), $file);
        $result = ftp_fput($this->getConnection(), $file, $stream, $this->transfer_mode, $size);
        fclose($stream);

        return $result;
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
        $remote_file_content = $this->get($file);

        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $content);
        fwrite($stream, $remote_file_content);
        rewind($stream);

        // We prevent ftp_fput from seeking local "file" ($h)
        ftp_set_option($this->getConnection(), FTP_AUTOSEEK, false);

        $result = $this->writeStream($file, $stream);

        fclose($stream);

        return $result;
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
        $stream = $this->readStream($file);
        fwrite($stream, $content);
        rewind($stream);

        $result = $this->writeStream($file, $stream);
        fclose($stream);

        return $result;
    }

    /**
     * List files in a directory
     *
     * @param  string $dirname
     * @return array
     */
    public function files($dirname = '.')
    {
        $listing = $this->listDirectoryContents($dirname);

        return array_values(array_filter($listing, function ($item) {
            return $item['type'] === 'file';
        }));
    }

    /**
     * List directories
     *
     * @param  string $dirname
     * @return array
     */
    public function directories($dirname = '.')
    {
        $listing = $this->listDirectoryContents($dirname);

        return array_values(array_filter($listing, function ($item) {
            return $item['type'] === 'directory';
        }));
    }

    /**
     * Create a directory
     *
     * @param  string $dirname
     * @param  int $mode
     *
     * @return boolean
     */
    public function makeDirectory($dirname, $mode = 0777)
    {
        $connection = $this->getConnection();

        $directories = explode('/', $dirname);

        foreach ($directories as $directory) {
            if (false === $this->makeActualDirectory($directory, $mode)) {
                $this->setConnectionRoot();
                return false;
            }
            ftp_chdir($connection, $directory);
        }

        $this->setConnectionRoot();

        return true;
    }

    /**
     * Create a directory.
     *
     * @param string $directory
     * @return bool
     */
    protected function makeActualDirectory($directory)
    {
        $connection = $this->getConnection();

        $directories = ftp_nlist($connection, '.') ?: [];

        // Remove unix characters from directory name
        array_walk($directories, function ($dir_name, $key) {
            return preg_match('~^\./.*~', $dir_name) ? substr($dir_name, 2) : $dir_name;
        });


        // Skip directory creation if it already exists
        if (in_array($directory, $directories, true)) {
            return true;
        }

        return ftp_mkdir($connection, $directory);
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
     * Check that a file exists
     *
     * @param string $filename
     * @return bool
     */
    public function exists($filename)
    {
        $listing = $this->listDirectoryContents();

        $dirname_info = array_filter($listing, function ($item) use ($filename) {
            return $item['name'] === $filename;
        });

        return count($dirname_info) !== 0;
    }

    /**
     * isFile alias of is_file.
     *
     * @param string $filename
     * @return bool
     */
    public function isFile($filename)
    {
        $listing = $this->listDirectoryContents();

        $dirname_info = array_filter($listing, function ($item) use ($filename) {
            return $item['type'] === 'file' && $item['name'] === $filename;
        });

        return count($dirname_info) !== 0;
    }

    /**
     * isDirectory alias of is_dir.
     *
     * @param string $dirname
     * @return bool
     */
    public function isDirectory($dirname)
    {
        $listing = $this->listDirectoryContents();

        $dirname_info = array_filter($listing, function ($item) use ($dirname) {
            return $item['type'] === 'directory' && $item['name'] === $dirname;
        });

        return count($dirname_info) !== 0;
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
        if ($this->exists($filename)) {
            return $filename;
        }

        return $filename;
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
     * List the directory content
     *
     * @param string $directory
     * @return array
     */
    protected function listDirectoryContents($directory = '.')
    {
        if ($directory && strpos($directory, '.') !== 0) {
            ftp_chdir($this->getConnection(), $directory);
        }

        $listing = @ftp_rawlist($this->getConnection(), '.') ?: [];

        $this->setConnectionRoot();

        return  $this->normalizeDirectoryListing($listing);
    }

    /**
     * Normalize directory content listing
     *
     * @param array $listing
     * @return array
     */
    private function normalizeDirectoryListing($listing)
    {
        $normalizedListing = [];

        foreach ($listing as $child) {
            $part = preg_split("/\s[0-9]{2}:[0-9]{2}\s/", $child);
            $chunks = preg_split("/\s+/", $child);

            list(
                $item['rights'],
                $item['number'],
                $item['user'],
                $item['group'],
                $item['size'],
                $item['month'],
                $item['day'],
                $item['time']
            ) = $chunks;

            $item["name"] = end($part);
            $item['type'] = $chunks[0][0] === 'd' ? 'directory' : 'file';

            array_splice($chunks, 0, 8);

            $normalizedListing[implode(" ", $chunks)] = $item;
        }

        return $normalizedListing;
    }

    /**
     * Read stream
     *
     * @param string $path
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
