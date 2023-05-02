<?php

declare(strict_types=1);

namespace Bow\Storage\Service;

use Bow\Http\UploadFile;
use Bow\Storage\Contracts\ServiceInterface;
use Bow\Storage\Exception\ResourceException;
use InvalidArgumentException;
use RuntimeException;
use FTP\Connection as FTPConnection;

class FTPService implements ServiceInterface
{
    /**
     * The Service configuration
     *
     * @var array
     */
    private array $config;

    /**
     * Ftp connection
     *
     * @var FTPConnection
     */
    private FTPConnection $connection;

    /**
     * Transfer mode
     *
     * @var int
     */
    private int $transfer_mode = FTP_BINARY;

    /**
     * Whether to use the passive mode.
     *
     * @var bool
     */
    private bool $use_passive_mode = true;

    /**
     * The FTPService Instance
     *
     * @var FTPService
     */
    private static ?FTPService $instance = null;

    /**
     * Cache the directory contents to avoid redundant server calls.
     *
     * @var array
     */
    private static array $cached_directory_contents = [];

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
    public static function configure(array $config): FTPService
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
     * @throws RuntimeException
     */
    public function connect()
    {
        $host = $this->config['hostname'];
        $port = $this->config['port'];
        $timeout = $this->config['timeout'];

        if ($this->config['tls']) {
            $connection = ftp_ssl_connect($host, $port, $timeout);
        } else {
            $connection = ftp_connect($host, $port, $timeout);
        }

        if (!$connection) {
            throw new RuntimeException(
                sprintf('Could not connect to %s:%s', $host, $port)
            );
        }

        // Set the FTP Connection resource
        $this->connection = $connection;

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
     * @return bool
     * @throws RuntimeException
     */
    private function login(): bool
    {
        ['username' => $username, 'password' => $password] = $this->config;

        // We disable error handling to avoid credentials leak :+1:
        set_error_handler(
            fn () => error_log("set_error_handler muted for hidden the ftp credential to user")
        );

        $is_logged_in = ftp_login($this->connection, $username, $password);

        restore_error_handler();

        if ($is_logged_in) {
            return true;
        }

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

    /**
     * Set the connection root.
     *
     * @param string $path
     * @return void
     */
    public function setConnectionRoot(string $path = '')
    {
        $base_path = $path ?: $this->config['root'];

        if ($base_path && (!@ftp_chdir($this->connection, $base_path))) {
            throw new RuntimeException('Root is invalid or does not exist: ' . $base_path);
        }

        // Store absolute path for further reference.
        ftp_pwd($this->connection);
    }

    /**
     * Get ftp connextion
     *
     * @return FTPConnection
     */
    public function getConnection(): FTPConnection
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
    public function store(UploadFile $file, ?string $location = null, array $option = []): array|bool
    {
        if (is_null($location)) {
            throw new InvalidArgumentException("Please define the store location");
        }

        $content = $file->getContent();
        $stream = fopen('php://temp', 'w+b');

        if (!$stream) {
            throw new RuntimeException("The error occured when store the file");
        }

        // Write the file content to the PHP temp opened file
        fwrite($stream, $content);
        rewind($stream);

        //
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
    public function append(string $file, string $content): bool
    {
        $stream = fopen('php://temp', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        // prevent ftp_fput from seeking local "file" ($h)
        ftp_set_option($this->getConnection(), FTP_AUTOSEEK, false);

        $size = ftp_size($this->getConnection(), $file);
        $result = ftp_fput($this->getConnection(), $file, $stream, $this->transfer_mode, $size);
        fclose($stream);

        return (bool) $result;
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
    public function files(string $dirname = '.'): array
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
    public function directories(string $dirname = '.'): array
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
    public function makeDirectory(string $dirname, int $mode = 0777): bool
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
    protected function makeActualDirectory(string $directory): bool
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

        return (bool) ftp_mkdir($connection, $directory);
    }

    /**
     * Get file content
     *
     * @param  string $filename
     * @return ?string
     */
    public function get(string $filename): ?string
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
    public function copy(string $target, string $source): bool
    {
        $source_stream = $this->readStream($source);
        $result = $this->writeStream($target, $source_stream);

        fclose($source_stream);

        return true;
    }

    /**
     * Rename or move a source file to a target file.
     *
     * @param string $target
     * @param string $source
     * @return bool
     */
    public function move(string $target, string $source): bool
    {
        return ftp_rename($this->getConnection(), $target, $source);
    }

    /**
     * Check that a file exists
     *
     * @param string $filename
     * @return bool
     */
    public function exists(string $filename): bool
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
    public function isFile(string $filename): bool
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
    public function isDirectory(string $dirname): bool
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
    public function path(string $filename): string
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
    public function delete(string $file): bool
    {
        $paths = is_array($file) ? $file : func_get_args();

        $success = true;

        foreach ($paths as $path) {
            if (!ftp_delete($this->getConnection(), $path)) {
                $success = false;
                break;
            }
        }

        return $success;
    }

    /**
     * Write stream
     *
     * @param string $path
     * @param resource $resource
     *
     * @return array|bool
     */
    private function writeStream(string $path, mixed $resource): array|bool
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
    private function normalizeDirectoryListing(array $listing): array
    {
        $normalizedListing = [];

        foreach ($listing as $child) {
            $chunks = preg_split("/\s+/", $child);

            list(
                $item['rights'],
                $item['number'],
                $item['user'],
                $item['group'],
                $item['size'],
                $item['month'],
                $item['day'],
                $item['time'],
                $item['name']
            ) = $chunks;

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
     * @throws ResourceException
     * @return resource|bool
     */
    private function readStream(string $path): mixed
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
