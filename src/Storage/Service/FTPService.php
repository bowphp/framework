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
    // Configuration keys
    private const CONFIG_HOSTNAME = 'hostname';
    private const CONFIG_PORT = 'port';
    private const CONFIG_TIMEOUT = 'timeout';
    private const CONFIG_USERNAME = 'username';
    private const CONFIG_PASSWORD = 'password';
    private const CONFIG_ROOT = 'root';
    private const CONFIG_TLS = 'tls';
    private const CONFIG_PASSIVE = 'passive';

    // Default configuration values
    private const DEFAULT_PORT = 21;
    private const DEFAULT_TIMEOUT = 90;
    private const DEFAULT_TLS = false;
    private const DEFAULT_PASSIVE = true;

    // Connection retry settings
    private const MAX_RETRY_ATTEMPTS = 3;
    private const RETRY_DELAY_SECONDS = 1;

    /**
     * The FTPService Instance
     *
     * @var ?FTPService
     */
    private static ?FTPService $instance = null;

    /**
     * Cache the directory contents to avoid redundant server calls
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
     * FTP connection
     *
     * @var ?FTPConnection
     */
    private ?FTPConnection $connection = null;

    /**
     * Transfer mode
     *
     * @var int
     */
    private int $transfer_mode = FTP_BINARY;

    /**
     * Whether to use the passive mode
     *
     * @var bool
     */
    private bool $use_passive_mode = true;

    /**
     * Whether the service is connected
     *
     * @var bool
     */
    private bool $is_connected = false;

    /**
     * FTPService constructor
     *
     * @param  array $config
     * @return void
     * @throws InvalidArgumentException
     */
    private function __construct(array $config)
    {
        $this->validateConfiguration($config);
        $this->config = $this->normalizeConfiguration($config);
        $this->use_passive_mode = (bool)($this->config[self::CONFIG_PASSIVE] ?? self::DEFAULT_PASSIVE);

        $this->connect();
    }

    /**
     * Validate required configuration parameters
     *
     * @param  array $config
     * @return void
     * @throws InvalidArgumentException
     */
    private function validateConfiguration(array $config): void
    {
        $required = [self::CONFIG_HOSTNAME, self::CONFIG_USERNAME, self::CONFIG_PASSWORD];

        foreach ($required as $key) {
            if (empty($config[$key])) {
                throw new InvalidArgumentException("Missing required FTP configuration: {$key}");
            }
        }
    }

    /**
     * Normalize configuration with default values
     *
     * @param  array $config
     * @return array
     */
    private function normalizeConfiguration(array $config): array
    {
        return array_merge([
            self::CONFIG_PORT => self::DEFAULT_PORT,
            self::CONFIG_TIMEOUT => self::DEFAULT_TIMEOUT,
            self::CONFIG_TLS => self::DEFAULT_TLS,
            self::CONFIG_ROOT => '',
            self::CONFIG_PASSIVE => self::DEFAULT_PASSIVE,
        ], $config);
    }

    /**
     * Connect to the FTP server with retry logic
     *
     * @return void
     * @throws RuntimeException
     */
    public function connect(): void
    {
        if ($this->is_connected && $this->connection !== null) {
            return;
        }

        $host = $this->config[self::CONFIG_HOSTNAME];
        $port = (int)$this->config[self::CONFIG_PORT];
        $timeout = (int)$this->config[self::CONFIG_TIMEOUT];
        $use_tls = (bool)$this->config[self::CONFIG_TLS];

        $connection = $this->attemptConnection($host, $port, $timeout, $use_tls);

        if (!$connection) {
            throw new RuntimeException(
                sprintf(
                    'Could not connect to %s://%s:%s after %d attempts',
                    $use_tls ? 'ftps' : 'ftp',
                    $host,
                    $port,
                    self::MAX_RETRY_ATTEMPTS
                )
            );
        }

        $this->connection = $connection;

        try {
            $this->login();
            $this->changePath();
            $this->activePassiveMode();
            $this->is_connected = true;
        } catch (RuntimeException $e) {
            $this->disconnect();
            throw $e;
        }
    }

    /**
     * Attempt FTP connection with retry logic
     *
     * @param  string $host
     * @param  int    $port
     * @param  int    $timeout
     * @param  bool   $use_tls
     * @return FTPConnection|false
     */
    private function attemptConnection(string $host, int $port, int $timeout, bool $use_tls): FTPConnection|false
    {
        $attempts = 0;
        $connection = false;

        while ($attempts < self::MAX_RETRY_ATTEMPTS && !$connection) {
            $attempts++;

            try {
                $connection = $use_tls
                    ? @ftp_ssl_connect($host, $port, $timeout)
                    : @ftp_connect($host, $port, $timeout);

                if ($connection) {
                    return $connection;
                }
            } catch (Exception $e) {
                // Suppress and continue to retry
            }

            if ($attempts < self::MAX_RETRY_ATTEMPTS) {
                sleep(self::RETRY_DELAY_SECONDS);
            }
        }

        return false;
    }

    /**
     * Authenticate with FTP server
     *
     * @return void
     * @throws RuntimeException
     */
    private function login(): void
    {
        $username = $this->config[self::CONFIG_USERNAME];
        $password = $this->config[self::CONFIG_PASSWORD];

        if (!@ftp_login($this->connection, $username, $password)) {
            $error = error_get_last();
            $message = $error['message'] ?? 'Authentication failed';

            throw new RuntimeException(
                sprintf(
                    'FTP login failed for %s@%s:%s - %s',
                    $username,
                    $this->config[self::CONFIG_HOSTNAME],
                    $this->config[self::CONFIG_PORT],
                    $message
                )
            );
        }
    }

    /**
     * Disconnect from the FTP server
     *
     * @return void
     */
    public function disconnect(): void
    {
        if ($this->connection !== null) {
            @ftp_close($this->connection);
            $this->is_connected = false;
        }
    }

    /**
     * Change working directory
     *
     * @param  string|null $path
     * @return void
     * @throws RuntimeException
     */
    public function changePath(?string $path = null): void
    {
        $this->ensureConnection();

        $target_path = $path ?? $this->config[self::CONFIG_ROOT];

        if ($target_path && !@ftp_chdir($this->connection, $target_path)) {
            throw new RuntimeException(
                sprintf('Failed to change directory to: %s', $target_path)
            );
        }
    }

    /**
     * Ensure FTP connection is active
     *
     * @return void
     * @throws RuntimeException
     */
    private function ensureConnection(): void
    {
        if (!$this->is_connected || $this->connection === null) {
            throw new RuntimeException('FTP connection is not established');
        }
    }

    /**
     * Configure passive mode for FTP connection
     *
     * @return void
     * @throws RuntimeException
     */
    private function activePassiveMode(): void
    {
        @ftp_set_option($this->connection, FTP_USEPASVADDRESS, false);

        if (!@ftp_pasv($this->connection, $this->use_passive_mode)) {
            throw new RuntimeException(
                sprintf(
                    'Failed to set passive mode (%s) for %s:%s',
                    $this->use_passive_mode ? 'enabled' : 'disabled',
                    $this->config[self::CONFIG_HOSTNAME],
                    $this->config[self::CONFIG_PORT]
                )
            );
        }
    }

    /**
     * Destructor - ensure connection is closed
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * Configure service
     *
     * @param  array $config
     * @return FTPService
     * @throws InvalidArgumentException
     */
    public static function configure(array $config): FTPService
    {
        if (static::$instance === null) {
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
     * Store uploaded file to FTP server
     *
     * @param  UploadedFile $file
     * @param  string|null  $location
     * @param  array        $option
     * @return bool
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function store(UploadedFile $file, ?string $location = null, array $option = []): bool
    {
        if ($location === null || trim($location) === '') {
            throw new InvalidArgumentException('Storage location must be specified');
        }

        $this->ensureConnection();

        $content = $file->getContent();
        $stream = $this->createTemporaryStream($content);

        try {
            $result = $this->writeStream($location, $stream);
        } finally {
            $this->closeStream($stream);
        }

        return $result;
    }

    /**
     * Create a temporary stream with content
     *
     * @param  string $content
     * @return resource
     * @throws RuntimeException
     */
    private function createTemporaryStream(string $content)
    {
        $stream = @fopen('php://temp', 'w+b');

        if (!$stream) {
            throw new RuntimeException('Failed to create temporary stream');
        }

        if (fwrite($stream, $content) === false) {
            fclose($stream);
            throw new RuntimeException('Failed to write to temporary stream');
        }

        rewind($stream);

        return $stream;
    }

    /**
     * Safely close a stream resource
     *
     * @param  resource $stream
     * @return void
     */
    private function closeStream($stream): void
    {
        if (is_resource($stream)) {
            @fclose($stream);
        }
    }

    /**
     * Write stream to FTP server
     *
     * @param  string   $file
     * @param  resource $resource
     * @return bool
     * @throws RuntimeException
     */
    private function writeStream(string $file, mixed $resource): bool
    {
        $this->ensureConnection();

        if (!is_resource($resource)) {
            throw new RuntimeException('Invalid stream resource provided');
        }

        return @ftp_fput($this->getConnection(), $file, $resource, $this->transfer_mode);
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
     * Append content to file
     *
     * @param  string $file
     * @param  string $content
     * @return bool
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function append(string $file, string $content): bool
    {
        if (trim($file) === '') {
            throw new InvalidArgumentException('File path cannot be empty');
        }

        $this->ensureConnection();

        $stream = @fopen('php://temp', 'r+');

        if (!$stream) {
            throw new RuntimeException('Failed to create temporary stream');
        }

        try {
            fwrite($stream, $content);
            rewind($stream);

            // Prevent ftp_fput from seeking local "file" ($stream)
            @ftp_set_option($this->getConnection(), FTP_AUTOSEEK, false);

            $size = @ftp_size($this->getConnection(), $file);
            return (bool)@ftp_fput($this->getConnection(), $file, $stream, $this->transfer_mode, $size);
        } finally {
            $this->closeStream($stream);
        }
    }

    /**
     * Prepend content to file
     *
     * @param  string $file
     * @param  string $content
     * @return bool
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws ResourceException
     */
    public function prepend(string $file, string $content): bool
    {
        if (trim($file) === '') {
            throw new InvalidArgumentException('File path cannot be empty');
        }

        $this->ensureConnection();

        $remote_file_content = $this->get($file);
        $stream = @fopen('php://temp', 'r+');

        if (!$stream) {
            throw new RuntimeException('Failed to create temporary stream');
        }

        try {
            fwrite($stream, $content);
            fwrite($stream, $remote_file_content ?? '');
            rewind($stream);

            // Prevent ftp_fput from seeking local "file" ($stream)
            @ftp_set_option($this->getConnection(), FTP_AUTOSEEK, false);

            return (bool)$this->writeStream($file, $stream);
        } finally {
            $this->closeStream($stream);
        }
    }

    /**
     * Get file content from FTP server
     *
     * @param  string $file
     * @return string|null
     * @throws ResourceException
     * @throws RuntimeException
     */
    public function get(string $file): ?string
    {
        if (trim($file) === '') {
            throw new InvalidArgumentException('File path cannot be empty');
        }

        $this->ensureConnection();

        $stream = $this->readStream($file);

        if (!$stream) {
            return null;
        }

        try {
            return stream_get_contents($stream);
        } finally {
            $this->closeStream($stream);
        }
    }

    /**
     * Read stream from FTP server
     *
     * @param  string $path
     * @return resource|false
     * @throws ResourceException
     * @throws RuntimeException
     */
    private function readStream(string $path): mixed
    {
        $this->ensureConnection();

        try {
            $stream = @fopen('php://temp', 'w+b');

            if (!$stream) {
                return false;
            }

            $result = @ftp_fget($this->getConnection(), $stream, $path, $this->transfer_mode);

            if ($result) {
                rewind($stream);
                return $stream;
            }

            $this->closeStream($stream);
            return false;
        } catch (Exception $exception) {
            throw new ResourceException(sprintf('File "%s" not found or inaccessible', $path));
        }
    }

    /**
     * Put content to file on FTP server
     *
     * @param  string $file
     * @param  string $content
     * @return bool
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws ResourceException
     */
    public function put(string $file, string $content): bool
    {
        if (trim($file) === '') {
            throw new InvalidArgumentException('File path cannot be empty');
        }

        $this->ensureConnection();

        $stream = $this->createTemporaryStream($content);

        try {
            return (bool)$this->writeStream($file, $stream);
        } finally {
            $this->closeStream($stream);
        }
    }

    /**
     * List files in a directory
     *
     * @param  string $dirname
     * @return array
     * @throws RuntimeException
     */
    public function files(string $dirname = '.'): array
    {
        $this->ensureConnection();

        $listing = $this->listDirectoryContents($dirname);

        return array_values(
            array_filter(
                $listing,
                fn($item) => $item['type'] === 'file'
            )
        );
    }

    /**
     * List directory contents
     *
     * @param  string $directory
     * @return array
     * @throws RuntimeException
     */
    protected function listDirectoryContents(string $directory = '.'): array
    {
        $this->ensureConnection();

        if ($directory && strpos($directory, '.') !== 0) {
            @ftp_chdir($this->getConnection(), $directory);
        }

        $listing = @ftp_rawlist($this->getConnection(), '.') ?: [];

        $this->changePath();

        return $this->normalizeDirectoryListing($listing);
    }

    /**
     * Normalize directory content listing from ftp_rawlist output
     *
     * @param  array $listing
     * @return array
     */
    private function normalizeDirectoryListing(array $listing): array
    {
        $normalized = [];

        foreach ($listing as $line) {
            $chunks = preg_split("/\s+/", $line);

            if (count($chunks) < 9) {
                // Invalid format, skip
                continue;
            }

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

            // The filename might contain spaces, so take everything after the 8th element
            $item['name'] = implode(' ', array_slice($chunks, 8));
            $item['type'] = $chunks[0][0] === 'd' ? 'directory' : 'file';

            $normalized[$item['name']] = $item;
        }

        return $normalized;
    }

    /**
     * List directories
     *
     * @param  string $dirname
     * @return array
     * @throws RuntimeException
     */
    public function directories(string $dirname = '.'): array
    {
        $this->ensureConnection();

        $listing = $this->listDirectoryContents($dirname);

        return array_values(
            array_filter(
                $listing,
                fn($item) => $item['type'] === 'directory'
            )
        );
    }

    /**
     * Create a directory recursively
     *
     * @param  string $dirname
     * @param  int    $mode
     * @return bool
     * @throws RuntimeException
     */
    public function makeDirectory(string $dirname, int $mode = 0777): bool
    {
        if (trim($dirname) === '') {
            throw new InvalidArgumentException('Directory name cannot be empty');
        }

        $this->ensureConnection();

        $connection = $this->getConnection();
        $directories = explode('/', trim($dirname, '/'));

        try {
            foreach ($directories as $directory) {
                if (!$this->makeActualDirectory($directory)) {
                    $this->changePath();
                    return false;
                }
                @ftp_chdir($connection, $directory);
            }

            $this->changePath();
            return true;
        } catch (Exception $e) {
            $this->changePath();
            throw new RuntimeException(
                sprintf('Failed to create directory "%s": %s', $dirname, $e->getMessage())
            );
        }
    }

    /**
     * Create a single directory
     *
     * @param  string $directory
     * @return bool
     * @throws RuntimeException
     */
    protected function makeActualDirectory(string $directory): bool
    {
        $this->ensureConnection();

        $connection = $this->getConnection();
        $directories = @ftp_nlist($connection, '.') ?: [];

        // Remove unix "./" prefix from directory names
        $directories = array_map(
            fn($dir) => preg_match('~^\./.*~', $dir) ? substr($dir, 2) : $dir,
            $directories
        );

        // Skip if directory already exists
        if (in_array($directory, $directories, true)) {
            return true;
        }

        return (bool)@ftp_mkdir($connection, $directory);
    }

    /**
     * Copy file from source to target
     *
     * @param  string $source
     * @param  string $target
     * @return bool
     * @throws InvalidArgumentException
     * @throws RuntimeException
     * @throws ResourceException
     */
    public function copy(string $source, string $target): bool
    {
        if (trim($source) === '' || trim($target) === '') {
            throw new InvalidArgumentException('Source and target paths cannot be empty');
        }

        $this->ensureConnection();

        $source_stream = $this->readStream($source);

        if (!$source_stream) {
            throw new ResourceException(sprintf('Cannot read source file: %s', $source));
        }

        try {
            return $this->writeStream($target, $source_stream);
        } finally {
            $this->closeStream($source_stream);
        }
    }

    /**
     * Rename or move a file from source to target
     *
     * @param  string $source
     * @param  string $target
     * @return bool
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function move(string $source, string $target): bool
    {
        if (trim($source) === '' || trim($target) === '') {
            throw new InvalidArgumentException('Source and target paths cannot be empty');
        }

        $this->ensureConnection();

        return (bool)@ftp_rename($this->getConnection(), $source, $target);
    }

    /**
     * Check if path is a file
     *
     * @param  string $file
     * @return bool
     * @throws RuntimeException
     */
    public function isFile(string $file): bool
    {
        if (trim($file) === '') {
            return false;
        }

        $this->ensureConnection();

        $listing = $this->listDirectoryContents();

        $matches = array_filter(
            $listing,
            fn($item) => $item['type'] === 'file' && $item['name'] === $file
        );

        return count($matches) > 0;
    }

    /**
     * Check if path is a directory
     *
     * @param  string $dirname
     * @return bool
     * @throws RuntimeException
     */
    public function isDirectory(string $dirname): bool
    {
        if (trim($dirname) === '') {
            return false;
        }

        $this->ensureConnection();

        $original_directory = @ftp_pwd($this->connection);

        // Test if we can change to the directory
        if (!@ftp_chdir($this->connection, $dirname)) {
            return false;
        }

        // Restore original directory
        @ftp_chdir($this->connection, $original_directory);

        return true;
    }

    /**
     * Resolves a path.
     * Give the absolute path of a path
     *
     * @param  string $file
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
     * Check if file or directory exists
     *
     * @param  string $path
     * @return bool
     * @throws RuntimeException
     */
    public function exists(string $path): bool
    {
        if (trim($path) === '') {
            return false;
        }

        $this->ensureConnection();

        $listing = $this->listDirectoryContents();

        $matches = array_filter(
            $listing,
            fn($item) => $item['name'] === $path
        );

        return count($matches) > 0;
    }

    /**
     * Delete file from FTP server
     *
     * @param  string $file
     * @return bool
     * @throws InvalidArgumentException
     * @throws RuntimeException
     */
    public function delete(string $file): bool
    {
        if (trim($file) === '') {
            throw new InvalidArgumentException('File path cannot be empty');
        }

        $this->ensureConnection();

        return (bool)@ftp_delete($this->getConnection(), $file);
    }
}
