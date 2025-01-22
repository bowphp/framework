<?php

declare(strict_types=1);

namespace Bow\Storage\Service;

use Bow\Http\UploadedFile;
use Bow\Storage\Contracts\ServiceInterface;
use Bow\Storage\Exception\ResourceException;
use Exception;
use FTP\Connection as FTPConnection;
use InvalidArgumentException;
use RuntimeException;

class FTPService implements ServiceInterface
{
    /**
     * The FTPService Instance
     *
     * @var ?FTPService
     */
    private static ?FTPService $instance = null;
    /**
     * Cache the directory contents to avoid redundant server calls.
     *
     * @var array
     */
    private static array $cached_directory_contents = [];
    /**
     * The Service configuration
     *
     * @var array
     */
    private array $config;
    /**
     * Ftp connection
     *
     * @var ?FTPConnection
     */
    private ?FTPConnection $connection;
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
     * Connect to the FTP server.
     *
     * @return void
     * @throws RuntimeException
     */
    public function connect(): void
    {
        $host = $this->config['hostname'];
        $port = (int)$this->config['port'];
        $timeout = (int)$this->config['timeout'];

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
        $this->changePath();
        $this->activePassiveMode();
    }

    /**
     * Make FTP Login.
     *
     * @return void
     * @throws RuntimeException
     */
    private function login(): void
    {
        ['username' => $username, 'password' => $password] = $this->config;

        $is_logged_in = ftp_login($this->connection, $username, $password);

        if ($is_logged_in) {
            return;
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
     * Disconnect from the FTP server.
     *
     * @return void
     */
    public function disconnect(): void
    {
        $this->connection = null;
    }

    /**
     * Change path.
     *
     * @param string|null $path
     * @return void
     */
    public function changePath(?string $path = null): void
    {
        $base_path = $path ?: $this->config['root'];

        if ($base_path && (!@ftp_chdir($this->connection, $base_path))) {
            throw new RuntimeException('Root is invalid or does not exist: ' . $base_path);
        }

        ftp_pwd($this->connection);
    }

    /**
     * Set the connections to passive mode.
     *
     * @throws RuntimeException
     */
    private function activePassiveMode(): void
    {
        @ftp_set_option($this->connection, FTP_USEPASVADDRESS, false);

        if (!ftp_pasv($this->connection, $this->use_passive_mode)) {
            throw new RuntimeException(
                'Could not set passive mode for connection: '
                . $this->config['hostname'] . '::' . $this->config['port']
            );
        }
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
     * Return the current working directory.
     *
     * @return mixed
     */
    public function getCurrentDirectory(): mixed
    {
        $path = pathinfo(ftp_pwd($this->connection));

        return $path['basename'];
    }

    /**
     * Store directly the upload file
     *
     * @param UploadedFile $file
     * @param string|null $location
     * @param array $option
     *
     * @return bool
     */
    public function store(UploadedFile $file, ?string $location = null, array $option = []): bool
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

        $result = $this->writeStream($location, $stream);

        fclose($stream);

        return $result;
    }

    /**
     * Write stream
     *
     * @param string $file
     * @param resource $resource
     *
     * @return bool
     */
    private function writeStream(string $file, mixed $resource): bool
    {
        return ftp_fput($this->getConnection(), $file, $resource, $this->transfer_mode);
    }

    /**
     * Get ftp connection
     *
     * @return FTPConnection
     */
    public function getConnection(): FTPConnection
    {
        return $this->connection;
    }

    /**
     * Append content a file.
     *
     * @param string $file
     * @param string $content
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

        return (bool)$result;
    }

    /**
     * Write to the beginning of a file specify
     *
     * @param string $file
     * @param string $content
     * @return bool
     * @throws ResourceException
     */
    public function prepend(string $file, string $content): bool
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

        return (bool)$result;
    }

    /**
     * Get file content
     *
     * @param string $file
     * @return ?string
     * @throws ResourceException
     */
    public function get(string $file): ?string
    {
        if (!$stream = $this->readStream($file)) {
            return null;
        }

        $contents = stream_get_contents($stream);

        fclose($stream);

        return $contents;
    }

    /**
     * Read stream
     *
     * @param string $path
     * @return mixed
     * @throws ResourceException
     */
    private function readStream(string $path): mixed
    {
        try {
            $stream = fopen('php://temp', 'w+b');

            if (!$stream) {
                return false;
            }

            $result = ftp_fget($this->getConnection(), $stream, $path, $this->transfer_mode);

            rewind($stream);

            if ($result) {
                return $stream;
            }

            fclose($stream);

            return false;
        } catch (Exception $exception) {
            throw new ResourceException(sprintf('"%s" not found.', $path));
        }
    }

    /**
     * Put other file content in given file
     *
     * @param string $file
     * @param string $content
     * @return bool
     * @throws ResourceException
     */
    public function put(string $file, string $content): bool
    {
        $stream = $this->readStream($file);

        if (!$stream) {
            return false;
        }

        fwrite($stream, $content);

        rewind($stream);

        $result = $this->writeStream($file, $stream);

        fclose($stream);

        return (bool)$result;
    }

    /**
     * List files in a directory
     *
     * @param string $dirname
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
     * List the directory content
     *
     * @param string $directory
     * @return array
     */
    protected function listDirectoryContents(string $directory = '.'): array
    {
        if ($directory && (strpos($directory, '.') !== 0)) {
            ftp_chdir($this->getConnection(), $directory);
        }

        $listing = @ftp_rawlist($this->getConnection(), '.') ?: [];

        $this->changePath();

        return $this->normalizeDirectoryListing($listing);
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
     * List directories
     *
     * @param string $dirname
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
     * @param string $dirname
     * @param int $mode
     * @return boolean
     */
    public function makeDirectory(string $dirname, int $mode = 0777): bool
    {
        $connection = $this->getConnection();

        $directories = explode('/', $dirname);

        foreach ($directories as $directory) {
            if (false === $this->makeActualDirectory($directory)) {
                $this->changePath();
                return false;
            }
            ftp_chdir($connection, $directory);
        }

        $this->changePath();

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

        return (bool)ftp_mkdir($connection, $directory);
    }

    /**
     * Copy the contents of a source file to a target file.
     *
     * @param string $source
     * @param string $target
     * @return bool
     * @throws ResourceException
     */
    public function copy(string $source, string $target): bool
    {
        $source_stream = $this->readStream($source);

        $result = $this->writeStream($target, $source_stream);

        fclose($source_stream);

        return $result;
    }

    /**
     * Rename or move a source file to a target file.
     *
     * @param string $source
     * @param string $target
     * @return bool
     */
    public function move(string $source, string $target): bool
    {
        return ftp_rename($this->getConnection(), $source, $target);
    }

    /**
     * isFile alias of is_file.
     *
     * @param string $file
     * @return bool
     */
    public function isFile(string $file): bool
    {
        $listing = $this->listDirectoryContents();

        $dirname_info = array_filter($listing, function ($item) use ($file) {
            return $item['type'] === 'file' && $item['name'] === $file;
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
        $original_directory = ftp_pwd($this->connection);

        // Test if you can change directory to $dirname
        // suppress errors in case $dir is not a file or not a directory
        if (!@ftp_chdir($this->connection, $dirname)) {
            return false;
        }

        // If it is a directory, then change the directory back to the original directory
        ftp_chdir($this->connection, $original_directory);

        return true;
    }

    /**
     * Resolves a path.
     * Give the absolute path of a path
     *
     * @param string $file
     * @return string
     */
    public function path(string $file): string
    {
        if ($this->exists($file)) {
            return $file;
        }

        return $file;
    }

    /**
     * Check that a file exists
     *
     * @param string $file
     * @return bool
     */
    public function exists(string $file): bool
    {
        $listing = $this->listDirectoryContents();

        $dirname_info = array_filter($listing, function ($item) use ($file) {
            return $item['name'] === $file;
        });

        return count($dirname_info) !== 0;
    }

    /**
     * Delete file
     *
     * @param string $file
     * @return bool
     */
    public function delete(string $file): bool
    {
        return ftp_delete($this->getConnection(), $file);
    }
}
