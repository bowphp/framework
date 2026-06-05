<?php

use Bow\Auth\Auth;
use Bow\Auth\Exception\AuthenticationException;
use Bow\Auth\Guards\GuardContract;
use Bow\Cache\Cache;
use Bow\Configuration\Loader;
use Bow\Container\Capsule;
use Bow\Database\Barry\Model;
use Bow\Database\Database as DB;
use Bow\Database\Exception\ConnectionException;
use Bow\Database\QueryBuilder;
use Bow\Event\Event;
use Bow\Http\Exception\HttpException;
use Bow\Http\HttpStatus;
use Bow\Http\Redirect;
use Bow\Http\Request;
use Bow\Http\Response;
use Bow\Mail\Contracts\MailAdapterInterface;
use Bow\Mail\Mail;
use Bow\Queue\QueueTask;
use Bow\Security\Crypto;
use Bow\Security\Hash;
use Bow\Security\Sanitize;
use Bow\Security\Tokenize;
use Bow\Session\Cookie;
use Bow\Session\Exception\SessionException;
use Bow\Session\Session;
use Bow\Storage\Exception\DiskNotFoundException;
use Bow\Storage\Exception\ServiceConfigurationNotFoundException;
use Bow\Storage\Exception\ServiceNotFoundException;
use Bow\Storage\Service\DiskFilesystemService;
use Bow\Storage\Service\FTPService;
use Bow\Storage\Service\S3Service;
use Bow\Storage\Storage;
use Bow\Support\Collection;
use Bow\Support\Env;
use Bow\Support\Str;
use Bow\Support\Util;
use Bow\Translate\Translator;
use Bow\Validation\Validate;
use Bow\Validation\Validator;
use Bow\View\View;
use Carbon\Carbon;
use Monolog\Logger;

/*
 * Global helper functions.
 *
 * Each helper is wrapped in `if (!function_exists(...))` so an application may
 * override any of them by declaring its own version before this file loads.
 * Most helpers are thin shortcuts over a framework class; the section banners
 * below mark where each topic begins.
 */

if (!function_exists('app')) {
    /**
     * Resolve the service container, or a binding out of it.
     *
     * With no arguments the container instance itself is returned; with a key
     * the matching binding is resolved (using `$setting` as constructor
     * parameters when provided).
     *
     * @param  ?string $key     Binding name to resolve, or null for the container
     * @param  array   $setting Parameters passed to makeWith() when resolving
     * @return mixed
     */
    function app(?string $key = null, array $setting = []): mixed
    {
        $capsule = Capsule::getInstance();

        if ($key == null && $setting == null) {
            return $capsule;
        }

        // No extra parameters: a plain resolution is enough.
        if (empty($setting)) {
            return $capsule->make($key);
        }

        return $capsule->makeWith($key, $setting);
    }
}

if (!function_exists('config')) {
    /**
     * Read or write application configuration.
     *
     * No key returns the configuration loader; a key alone reads the value;
     * a key with a value writes (and returns) it.
     *
     * @param  string|null $key     Dotted configuration key
     * @param  mixed       $setting Value to set, or null to read
     * @return Loader|mixed
     * @throws Exception
     */
    function config(?string $key = null, mixed $setting = null): mixed
    {
        $config = Loader::getInstance();

        if (is_null($key)) {
            return $config;
        }

        if (is_null($setting)) {
            return $config[$key];
        }

        return $config[$key] = $setting;
    }
}

if (!function_exists('response')) {
    /**
     * Get the shared Response instance from the container.
     *
     * @return Response
     */
    function response(): Response
    {
        /**
         * @var Response $response
         */
        $response = app('response');

        return $response;
    }
}

if (!function_exists('request')) {
    /**
     * Get the shared Request instance from the container.
     *
     * @return Request
     */
    function request(): Request
    {
        /**
         * @var Request $request
         */
        $request = app('request');

        return $request;
    }
}

if (!function_exists('db')) {
    /**
     * Get the database manager, optionally on another connection.
     *
     * With no arguments the current connection is returned. When `$cb` is
     * given it runs against `$name`, then the previous connection is restored.
     *
     * Note: registered under the `db` guard but the function is named
     * `app_db()`; call it as `app_db(...)`.
     *
     * @param  string|null   $name Connection name to switch to
     * @param  callable|null $cb   Work to run on that connection, then revert
     * @return DB
     * @throws ConnectionException
     */
    function app_db(?string $name = null, ?callable $cb = null): DB
    {
        if (func_num_args() == 0) {
            return DB::getInstance();
        }

        $old_connection = DB::getConnectionName();

        if ($old_connection === $name) {
            $instance = DB::getInstance();
        } else {
            $instance = DB::connection($name);
        }

        // When callback is defined, we execute the callback
        // set the old connection name after execution
        if (is_callable($cb)) {
            $cb();
            $instance = DB::connection($old_connection);
        }

        return $instance;
    }
}

if (!function_exists('view')) {
    /**
     * Render a view template through View::parse().
     *
     * `$data` may be passed as the status code directly (e.g. `view('404', 404)`),
     * in which case it is treated as `$code` and the data set is left empty.
     *
     * @param  string    $template View name
     * @param  array|int $data     View data, or the HTTP status code
     * @param  int       $code     HTTP status code
     * @return View
     */
    function view(string $template, int|array $data = [], int $code = 200): View
    {
        // Allow the status code to be supplied in the $data slot.
        if (is_int($data)) {
            $code = $data;

            $data = [];
        }

        response()
            ->status($code);

        return View::parse($template, $data);
    }
}

if (!function_exists('table')) {
    /**
     * Get a query builder for a table (optionally on another connection).
     *
     * @param      string  $name      Table name
     * @param      ?string $connexion Connection to switch to first
     * @return     QueryBuilder
     * @throws     ConnectionException
     * @deprecated Use app_db_table() instead.
     * @see        app_db_table()
     */
    function table(string $name, ?string $connexion = null): QueryBuilder
    {
        if (is_string($connexion)) {
            app_db($connexion);
        }

        return DB::table($name);
    }
}

if (!function_exists('app_db_table')) {
    /**
     * Get a query builder for a table (optionally on another connection).
     *
     * @param  string  $name      Table name
     * @param  ?string $connexion Connection to switch to first
     * @return QueryBuilder
     * @throws ConnectionException
     */
    function app_db_table(string $name, ?string $connexion = null): QueryBuilder
    {
        if (is_string($connexion)) {
            app_db($connexion);
        }

        return DB::table($name);
    }
}

if (!function_exists('get_last_insert_id')) {
    /**
     * Returns the last ID following an INSERT query
     * on a table whose ID is auto_increment.
     *
     * @param  string|null $name Sequence/connection name, if required
     * @return int
     */
    function get_last_insert_id(?string $name = null): int
    {
        return DB::lastInsertId($name);
    }
}

if (!function_exists('app_db_select')) {
    /**
     * Run a raw SELECT query.
     *
     * app_db_select('SELECT * FROM users');
     *
     * @param  string $sql  SQL statement, may contain bindings
     * @param  array  $data Values bound to the statement
     * @return int|array|stdClass
     */
    function app_db_select(string $sql, array $data = []): array|int|stdClass
    {
        return DB::select($sql, $data);
    }
}

if (!function_exists('app_db_select_one')) {
    /**
     * Run a raw SELECT query and return a single row.
     *
     * @param  string $sql  SQL statement, may contain bindings
     * @param  array  $data Values bound to the statement
     * @return int|array|StdClass
     */
    function app_db_select_one(string $sql, array $data = []): array|int|StdClass
    {
        return DB::selectOne($sql, $data);
    }
}

if (!function_exists('app_db_insert')) {
    /**
     * Run a raw INSERT query.
     *
     * @param  string $sql  SQL statement, may contain bindings
     * @param  array  $data Values bound to the statement
     * @return int Number of affected rows
     */
    function app_db_insert(string $sql, array $data = []): int
    {
        return DB::insert($sql, $data);
    }
}

if (!function_exists('app_db_delete')) {
    /**
     * Run a raw DELETE query.
     *
     * @param  string $sql  SQL statement, may contain bindings
     * @param  array  $data Values bound to the statement
     * @return int Number of affected rows
     */
    function app_db_delete(string $sql, array $data = []): int
    {
        return DB::delete($sql, $data);
    }
}

if (!function_exists('app_db_update')) {
    /**
     * Run a raw UPDATE query.
     *
     * @param  string $sql  SQL statement, may contain bindings
     * @param  array  $data Values bound to the statement
     * @return int Number of affected rows
     */
    function app_db_update(string $sql, array $data = []): int
    {
        return DB::update($sql, $data);
    }
}

if (!function_exists('app_db_statement')) {
    /**
     * Run a schema/DDL statement (CREATE, ALTER, RENAME, DROP, ...).
     *
     * @param  string $sql SQL statement
     * @return int
     */
    function app_db_statement(string $sql): int
    {
        return DB::statement($sql);
    }
}

if (!function_exists('debug')) {
    /**
     * Dump one or more variables with colourised, typed output.
     *
     * Accepts any number of arguments; each is sanitised then handed to
     * Util::debug().
     *
     * @return void
     */
    function debug(): void
    {
        array_map(
            function ($x) {
                call_user_func_array([Util::class, 'debug'], [$x]);
            },
            secure(func_get_args())
        );
    }
}

if (!function_exists("sep")) {
    /**
     * Get the OS-specific directory separator.
     *
     * @return string
     */
    function sep(): string
    {
        return call_user_func([Util::class, 'sep']);
    }
}

if (!function_exists('create_csrf_token')) {
    /**
     * Create (or fetch) the current CSRF token payload.
     *
     * @param  int|null $time Lifetime in seconds for the generated token
     * @return ?array The token data (token, field, expire_at), or null
     * @throws SessionException
     */
    function create_csrf_token(?int $time = null): ?array
    {
        return Tokenize::csrf($time);
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Get the current CSRF token string.
     *
     * @return string
     * @throws HttpException When no token could be generated
     * @throws SessionException
     */
    function csrf_token(): string
    {
        $csrf = (array) create_csrf_token();

        if (count($csrf) == 0) {
            throw new HttpException(
                "CSRF token is not generated",
                500
            );
        }

        return $csrf['token'];
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Get the ready-made hidden CSRF input field.
     *
     * @return string
     * @throws HttpException When no token could be generated
     * @throws SessionException
     */
    function csrf_field(): string
    {
        $csrf = (array) create_csrf_token();

        if (count($csrf) == 0) {
            throw new HttpException(
                "CSRF token is not generated",
                500
            );
        }

        return $csrf['field'];
    }
}

if (!function_exists('method_field')) {
    /**
     * Build a hidden input that spoofs the HTTP method (PUT, PATCH, DELETE).
     *
     * @param  string $method HTTP verb to spoof
     * @return string
     */
    function method_field(string $method): string
    {
        $method = strtoupper($method);

        return '<input type="hidden" name="_method" value="' . $method . '">';
    }
}

if (!function_exists('gen_csrf_token')) {
    /**
     * Generate a fresh, standalone token string (not stored in the session).
     *
     * @return string
     */
    function gen_csrf_token(): string
    {
        return Tokenize::make();
    }
}

if (!function_exists('verify_csrf')) {
    /**
     * Verify a submitted CSRF token against the stored one.
     *
     * @param  string $token  Token received from the request
     * @param  bool   $strict Also enforce token expiry when true
     * @return bool
     * @throws SessionException
     */
    function verify_csrf(string $token, bool $strict = false): bool
    {
        return Tokenize::verify($token, $strict);
    }
}

if (!function_exists('csrf_time_is_expired')) {
    /**
     * Check whether the stored CSRF token has expired.
     *
     * @param  string|null $time Reference time, defaults to now
     * @return bool
     * @throws SessionException
     */
    function csrf_time_is_expired(?string $time = null): bool
    {
        return Tokenize::csrfExpired($time);
    }
}

if (!function_exists('response_json')) {
    /**
     * Send a JSON response.
     *
     * @param  array|object $data    Payload to encode
     * @param  int          $code    HTTP status code
     * @param  array        $headers Extra response headers
     * @return string
     */
    function response_json(array|object $data, int $code = 200, array $headers = []): string
    {
        return response()->json($data, $code, $headers);
    }
}

if (!function_exists('response_download')) {
    /**
     * Send a file as a download response.
     *
     * @param  string      $file     Path to the file on disk
     * @param  null|string $filename Name presented to the client
     * @param  array       $headers  Extra response headers
     * @return string
     */
    function response_download(string $file, ?string $filename = null, array $headers = []): string
    {
        return response()->download($file, $filename, $headers);
    }
}

if (!function_exists('set_response_status_code')) {
    /**
     * Set the HTTP response status code.
     *
     * @param  int $code
     * @return mixed
     */
    function set_response_status_code(int $code): mixed
    {
        return response()->status($code);
    }
}

if (!function_exists('sanitize')) {
    /**
     * Sanitize a value (numeric values are returned untouched).
     *
     * @param  mixed $data
     * @return mixed
     */
    function sanitize(mixed $data): mixed
    {
        if (is_numeric($data)) {
            return $data;
        }

        return Sanitize::make($data);
    }
}

if (!function_exists('secure')) {
    /**
     * Sanitize a value in strict/secure mode (numeric values pass through).
     *
     * @param  mixed $data
     * @return mixed
     */
    function secure(mixed $data): mixed
    {
        if (is_numeric($data)) {
            return $data;
        }

        return Sanitize::make($data, true);
    }
}

if (!function_exists('set_response_header')) {
    /**
     * Add a header to the outgoing response.
     *
     * @param  string $key
     * @param  string $value
     * @return void
     */
    function set_response_header(string $key, string $value): void
    {
        response()->withHeader($key, $value);
    }
}

if (!function_exists('get_response_header')) {
    /**
     * Read a header from the incoming request.
     *
     * @param  string $key
     * @return string|null
     */
    function get_response_header(string $key): ?string
    {
        return request()->getHeader($key);
    }
}

if (!function_exists('redirect')) {
    /**
     * Get the redirector, optionally redirecting straight to a path.
     *
     * @param  string|null $path Target to redirect to, or null for the instance
     * @return Redirect
     */
    function redirect(?string $path = null): Redirect
    {
        $redirect = Redirect::getInstance();

        if ($path !== null) {
            $redirect->to($path);
        }

        return $redirect;
    }
}

if (!function_exists('url')) {
    /**
     * Build an absolute URL from the current request base.
     *
     * Passing an array as the first argument is treated as the query string
     * parameters (the path is then the current URL).
     *
     * @param  string|array $url        Path to append, or query parameters
     * @param  array        $parameters Query string parameters
     * @return string
     */
    function url(string|array $url = '', array $parameters = []): string
    {
        $current = trim(request()->url(), '/');

        // First argument given as parameters: keep the current path.
        if (is_array($url)) {
            $parameters = $url;

            $url = '';
        }

        if (is_string($url)) {
            $current .= '/' . trim($url, '/');
        }

        if (count($parameters) > 0) {
            $current .= '?' . http_build_query($parameters);
        }

        return $current;
    }
}

if (!function_exists('pdo')) {
    /**
     * Get the underlying PDO instance.
     *
     * @return PDO
     */
    function pdo(): PDO
    {
        return DB::getPdo();
    }
}

if (!function_exists('set_pdo')) {
    /**
     * Replace the underlying PDO instance.
     *
     * @param  PDO $pdo
     * @return PDO The newly set instance
     */
    function set_pdo(PDO $pdo): PDO
    {
        DB::setPdo($pdo);

        return pdo();
    }
}

if (!function_exists('collect')) {
    /**
     * Wrap an array in a Collection.
     *
     * @param  array $data
     * @return Collection
     */
    function collect(array $data = []): Collection
    {
        return new Collection($data);
    }
}

if (!function_exists('encrypt')) {
    /**
     * Encrypt data using the application security key.
     *
     * Returns an authenticated payload (random IV + HMAC), so encrypting the
     * same value twice yields different ciphertexts.
     *
     * @param  string $data
     * @return string
     */
    function encrypt(string $data): string
    {
        return Crypto::encrypt($data);
    }
}

if (!function_exists('decrypt')) {
    /**
     * Decrypt a value previously produced by encrypt().
     *
     * Fails closed: returns false when the payload has been tampered with or
     * was encrypted with a different key.
     *
     * @param  string $data
     * @return string|bool
     */
    function decrypt(string $data): string|bool
    {
        return Crypto::decrypt($data);
    }
}

// ===== Database: transactions =====

if (!function_exists('app_db_transaction')) {
    /**
     * Begin a database transaction.
     *
     * @return void
     */
    function app_db_transaction(): void
    {
        DB::startTransaction();
    }
}

if (!function_exists('app_db_transaction_started')) {
    /**
     * Check whether a database transaction is currently open.
     *
     * @return bool
     */
    function app_db_transaction_started(): bool
    {
        return DB::inTransaction();
    }
}

if (!function_exists('app_db_rollback')) {
    /**
     * Roll back the current database transaction.
     *
     * @return void
     */
    function app_db_rollback(): void
    {
        DB::rollback();
    }
}

if (!function_exists('app_db_commit')) {
    /**
     * Commit the current database transaction.
     *
     * @return void
     */
    function app_db_commit(): void
    {
        DB::commit();
    }
}

if (!function_exists('event')) {
    /**
     * Get the event dispatcher, or emit an event.
     *
     * Called with no arguments it returns the dispatcher; otherwise the first
     * argument is the event name and the rest are passed to its listeners.
     *
     * @param  mixed ...$args Event name followed by its payload
     * @return mixed
     */
    function event(): mixed
    {
        $args = func_get_args();

        $event = Event::getInstance();

        if (count($args) === 0) {
            return $event;
        }

        return call_user_func_array([$event, "emit"], $args);
    }
}

if (!function_exists('app_event')) {
    /**
     * Get the event dispatcher, or emit an event.
     *
     * @param  mixed ...$args Event name followed by its payload
     * @return mixed
     * @see    event() Identical behaviour; event() is the preferred name.
     */
    function app_event(): mixed
    {
        $args = func_get_args();

        $event = Event::getInstance();

        if (count($args) === 0) {
            return $event;
        }

        return call_user_func_array([$event, "emit"], $args);
    }
}

if (!function_exists('flash')) {
    /**
     * Store a one-request flash message in the session.
     *
     * @param  string $key     Flash key
     * @param  string $message Message to store
     * @return mixed
     * @throws SessionException
     */
    function flash(string $key, string $message): mixed
    {
        return Session::getInstance()
            ->flash($key, $message);
    }
}

if (!function_exists('app_flash')) {
    /**
     * Store a one-request flash message in the session.
     *
     * @param  string $key     Flash key
     * @param  string $message Message to store
     * @return mixed
     * @throws SessionException
     * @see    flash() Identical behaviour; flash() is the preferred name.
     */
    function app_flash(string $key, string $message): mixed
    {
        return Session::getInstance()
            ->flash($key, $message);
    }
}

if (!function_exists('email')) {
    /**
     * Send an email, or get the mailer instance.
     *
     * With no view the mailer instance is returned; otherwise the view is
     * rendered and sent.
     *
     * @param  null|string   $view View name for the message body
     * @param  array         $data Data bound to the view
     * @param  callable|null $cb   Builder callback to configure the message
     * @return MailAdapterInterface|bool
     */
    function email(
        ?string $view = null,
        ?array $data = [],
        ?callable $cb = null
    ): MailAdapterInterface|bool {
        if ($view === null) {
            return Mail::getInstance();
        }

        return Mail::send($view, $data, $cb);
    }
}

if (!function_exists('app_email')) {
    /**
     * Send an email, or get the mailer instance.
     *
     * @param  null|string   $view View name for the message body
     * @param  array         $data Data bound to the view
     * @param  callable|null $cb   Builder callback to configure the message
     * @return MailAdapterInterface|bool
     * @see    email() Identical behaviour; email() is the preferred name.
     */
    function app_email(
        ?string $view = null,
        ?array $data = [],
        ?callable $cb = null
    ): MailAdapterInterface|bool {
        if ($view === null) {
            return Mail::getInstance();
        }

        return Mail::send($view, $data, $cb);
    }
}

if (!function_exists('raw_email')) {
    /**
     * Send a plain (non-templated) email.
     *
     * @param  string $to      Recipient address
     * @param  string $subject Subject line
     * @param  string $message Message body
     * @param  array  $headers Extra mail headers
     * @return bool
     */
    function raw_email(string $to, string $subject, string $message, array $headers = []): bool
    {
        return Mail::raw($to, $subject, $message, $headers);
    }
}

if (!function_exists('session')) {
    /**
     * Get the session manager, or read a session value.
     *
     * @param  string|null $key     Key to read, or null for the manager
     * @param  mixed       $default Value returned when the key is absent
     * @return mixed
     * @throws SessionException
     */
    function session(?string $key = null, mixed $default = null): mixed
    {
        if ($key == null) {
            return Session::getInstance();
        }

        return Session::getInstance()->get($key, $default);
    }
}

if (!function_exists('cookie')) {
    /**
     * Read or write cookies.
     *
     * No key returns all cookies; a key alone reads one; a key with data
     * writes it.
     *
     * @param  string|null $key        Cookie name
     * @param  mixed       $data       Value to write, or null to read
     * @param  int         $expiration Lifetime in seconds when writing
     * @return string|array|object|null
     */
    function cookie(
        ?string $key = null,
        mixed $data = null,
        int $expiration = 3600
    ): string|array|object|null {
        if ($key === null) {
            return Cookie::all();
        }

        if ($data == null) {
            return Cookie::get($key);
        }

        return Cookie::set($key, $data, $expiration);
    }
}

if (!function_exists('validator')) {
    /**
     * Validate input against a set of rules.
     *
     * @param  array $inputs   Data to validate
     * @param  array $rules    Validation rules keyed by field
     * @param  array $messages Custom error messages
     * @return Validate
     */
    function validator(array $inputs, array $rules, array $messages = []): Validate
    {
        return Validator::make($inputs, $rules, $messages);
    }
}

if (!function_exists('route')) {
    /**
     * Build a URL for a named route.
     *
     * Named placeholders in the route are filled from `$data`; leftover
     * entries become the query string. Passing a bool as `$data` is treated
     * as the `$absolute` flag.
     *
     * @param  string     $name     Route name
     * @param  bool|array $data     Placeholder values, or the absolute flag
     * @param  bool       $absolute Prefix with APP_URL when true
     * @return string
     * @throws InvalidArgumentException When the route or a placeholder is missing
     */
    function route(string $name, bool|array $data = [], bool $absolute = false): string
    {
        // Allow route('name', true) to mean "absolute, no parameters".
        if (is_bool($data)) {
            $absolute = $data;
            $data = [];
        }

        $url = config('app.routes.' . $name);

        if (is_null($url)) {
            throw new InvalidArgumentException(
                'The route named ' . $name . ' does not define.',
                E_USER_ERROR
            );
        }

        // Substitute :placeholders (optional ones end with "?").
        if (preg_match_all('/:([a-zA-Z0-9_]+\??)/', $url, $matches)) {
            $keys = end($matches);
            foreach ($keys as $key) {
                if (preg_match("/\?$/", $key)) {
                    $valid_key = trim($key, "?");
                    $value = $data[$valid_key] ?? "";
                    unset($data[$valid_key]);
                } else {
                    if (!isset($data[$key])) {
                        throw new InvalidArgumentException("Route: The $key key is not provide");
                    }
                    $value = $data[$key];
                    unset($data[$key]);
                }
                $url = str_replace(':' . $key, $value, $url);
                $url = str_replace("//", "/", $url);
            }
        }

        // Remaining data becomes the query string.
        if (count($data) > 0) {
            $url = $url . '?' . http_build_query($data);
        }

        $url = rtrim(app_env('APP_URI_PREFIX', '/'), '/') . '/' . ltrim($url, '/');

        if ($absolute) {
            $url = rtrim(app_env('APP_URL'), '/') . '/' . ltrim($url, '/');
        }

        return $url;
    }
}

if (!function_exists('e')) {
    /**
     * Escape HTML special characters in a string.
     *
     * @param  ?string $value
     * @return string
     */
    function e(?string $value = null): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('storage_service')) {
    /**
     * Resolve a remote storage service (FTP, S3, ...).
     *
     * @param  string $service Service name
     * @return FTPService|S3Service
     * @throws ServiceConfigurationNotFoundException
     * @throws ServiceNotFoundException
     */
    function storage_service(string $service): S3Service|FTPService
    {
        return Storage::service($service);
    }
}

if (!function_exists('app_storage')) {
    /**
     * Get a local filesystem disk.
     *
     * @param  string $disk Disk name
     * @return DiskFilesystemService
     * @throws DiskNotFoundException
     */
    function app_storage(string $disk): DiskFilesystemService
    {
        return Storage::local($disk);
    }
}

if (!function_exists('cache')) {
    /**
     * Get the cache instance, or read/write a cache entry.
     *
     * No key returns the cache instance; a key alone reads it; a key with a
     * value stores it for `$ttl` seconds.
     *
     * @param  ?string $key   Cache key
     * @param  mixed   $value Value to store, or null to read
     * @param  ?int    $ttl   Time-to-live in seconds when writing
     * @return mixed
     * @throws ErrorException
     */
    function cache(?string $key = null, mixed $value = null, ?int $ttl = null): mixed
    {
        $instance = Cache::getInstance();

        if ($key === null) {
            return $instance;
        }

        if ($value === null) {
            return $instance->get($key);
        }

        return $instance->set($key, $value, $ttl);
    }
}

if (!function_exists('redirect_back')) {
    /**
     * Redirect to the previous page.
     *
     * @param  int $status HTTP status code
     * @return Redirect
     */
    function redirect_back(int $status = 302): Redirect
    {
        return redirect()->back($status);
    }
}

if (!function_exists('app_now')) {
    /**
     * Get the current time as a Carbon instance.
     *
     * @return Carbon
     */
    function app_now(): Carbon
    {
        return Carbon::now();
    }
}

if (!function_exists('app_hash')) {
    /**
     * Hash a value, or verify one against an existing hash.
     *
     * With `$hash_value` it checks the value against the hash; otherwise it
     * returns a new hash.
     *
     * @param  string $data       Value to hash or verify
     * @param  string|null $hash_value Existing hash to verify against
     * @return bool|string Boolean when verifying, string when hashing
     */
    function app_hash(string $data, ?string $hash_value = null): bool|string
    {
        if (!is_null($hash_value)) {
            return Hash::check($data, $hash_value);
        }

        return Hash::make($data);
    }
}

if (!function_exists('bow_hash')) {
    /**
     * Hash a value, or verify one against an existing hash.
     *
     * @param      string $data       Value to hash or verify
     * @param      string|null $hash_value Existing hash to verify against
     * @return     bool|string
     * @deprecated Use app_hash() instead.
     * @see        app_hash()
     */
    function bow_hash(string $data, ?string $hash_value = null): bool|string
    {
        return app_hash($data, $hash_value);
    }
}

if (!function_exists('app_trans')) {
    /**
     * Translate a key, or get the translator instance.
     *
     * No key returns the translator. Passing a bool as `$data` is treated as
     * the `$choose` (pluralisation) flag.
     *
     * @param  string|null $key    Translation key
     * @param  array       $data   Replacement values
     * @param  bool        $choose Pluralisation flag
     * @return string|Translator
     */
    function app_trans(
        ?string $key = null,
        array $data = [],
        bool $choose = false
    ): string|Translator {
        if (is_null($key)) {
            return Translator::getInstance();
        }

        if (is_bool($data)) {
            $choose = $data;
            $data = [];
        }

        return Translator::translate($key, $data, $choose);
    }
}

if (!function_exists('t')) {
    /**
     * Translate a key.
     *
     * @param  string $key    Translation key
     * @param  array  $data   Replacement values
     * @param  bool   $choose Pluralisation flag
     * @return string|Translator
     * @see    app_trans()
     */
    function t(
        string $key,
        array $data = [],
        bool $choose = false
    ): string|Translator {
        return app_trans($key, $data, $choose);
    }
}

if (!function_exists('__')) {
    /**
     * Translate a key.
     *
     * @param  string $key    Translation key
     * @param  array  $data   Replacement values
     * @param  bool   $choose Pluralisation flag
     * @return string|Translator
     * @see    app_trans()
     */
    function __(
        string $key,
        array $data = [],
        bool $choose = false
    ): string|Translator {
        return app_trans($key, $data, $choose);
    }
}

if (!function_exists('app_env')) {
    /**
     * Read an environment variable.
     *
     * Returns `$default` when the environment has not been loaded yet.
     *
     * @param  string $key     Variable name
     * @param  mixed  $default Fallback value
     * @return ?string
     */
    function app_env(string $key, mixed $default = null): ?string
    {
        try {
            $env = Env::getInstance();

            if ($env->isLoaded()) {
                return $env->get($key, $default);
            }
        } catch (\Bow\Application\Exception\ApplicationException $e) {
            // Environment not loaded, return default
        }

        return $default;
    }
}

if (!function_exists('app_assets')) {
    /**
     * Build a public URL for an asset under the asset prefix.
     *
     * @param  string $filename Asset path relative to the asset root
     * @return string
     */
    function app_assets(string $filename): string
    {
        return rtrim(app_env("APP_ASSET_PREFIX", "/"), "/") . "/" . trim($filename, "/");
    }
}

if (!function_exists('app_abort')) {
    /**
     * Abort the request with an HTTP error.
     *
     * Falls back to the standard status message when none is given.
     *
     * @param  int    $code    HTTP status code
     * @param  string $message Error message
     * @return Response
     * @throws HttpException Always thrown to interrupt execution
     */
    function app_abort(int $code = 500, string $message = ''): Response
    {
        if (strlen($message) == 0) {
            $message = HttpStatus::getMessage($code);
        }

        throw new HttpException($message, $code);
    }
}

if (!function_exists('app_abort_if')) {
    /**
     * Abort the request only when the given condition is true.
     *
     * @param  bool   $boolean Condition that triggers the abort
     * @param  int    $code    HTTP status code
     * @param  string $message Error message
     * @return Response|null Null when the condition is false
     * @throws HttpException When the condition is true
     */
    function app_abort_if(
        bool $boolean,
        int $code,
        string $message = ''
    ): Response|null {
        if ($boolean) {
            return app_abort($code, $message);
        }

        return null;
    }
}

if (!function_exists('app_mode')) {
    /**
     * Get the current application environment (lower-cased APP_ENV).
     *
     * @return string
     */
    function app_mode(): string
    {
        return strtolower(app_env('APP_ENV'));
    }
}

if (!function_exists('app_in_debug')) {
    /**
     * Determine whether debug mode (APP_DEBUG) is enabled.
     *
     * @return bool
     */
    function app_in_debug(): bool
    {
        return (bool) app_env('APP_DEBUG');
    }
}

if (!function_exists('client_locale')) {
    /**
     * Get the client's preferred request language.
     *
     * @return ?string
     */
    function client_locale(): ?string
    {
        return request()->lang();
    }
}

if (!function_exists('old')) {
    /**
     * Get a value submitted on the previous request.
     *
     * @param  string $key      Input field name
     * @param  mixed  $fullback Value returned when the field is absent
     * @return mixed
     */
    function old(string $key, mixed $fullback = null): mixed
    {
        return request()->old($key, $fullback);
    }
}

if (!function_exists('auth')) {
    /**
     * Get the auth manager, or a specific guard.
     *
     * @param      string|null $guard Guard name, or null for the manager
     * @return     GuardContract
     * @throws     AuthenticationException
     * @deprecated Use app_auth() instead.
     * @see        app_auth()
     */
    function auth(?string $guard = null): GuardContract
    {
        $auth = Auth::getInstance();

        if (is_null($guard)) {
            return $auth;
        }

        return $auth->guard($guard);
    }
}

if (!function_exists('app_auth')) {
    /**
     * Get the auth manager, or a specific guard.
     *
     * @param  string|null $guard Guard name, or null for the manager
     * @return GuardContract
     * @throws AuthenticationException
     */
    function app_auth(?string $guard = null): GuardContract
    {
        $auth = Auth::getInstance();

        if (is_null($guard)) {
            return $auth;
        }

        return $auth->guard($guard);
    }
}

if (!function_exists('logger')) {
    /**
     * Get the application logger.
     *
     * @return Logger
     */
    function logger(): Logger
    {
        return app('logger');
    }
}

if (!function_exists('app_logger')) {
    /**
     * Get the application logger.
     *
     * @return Logger
     * @see    logger() Identical behaviour; logger() is the preferred name.
     */
    function app_logger(): Logger
    {
        return app('logger');
    }
}


if (!function_exists('str_slug')) {
    /**
     * Convert a string into a URL-friendly slug.
     *
     * @param  string $str String to slugify
     * @param  string $sep Word separator
     * @return string
     */
    function str_slug(string $str, string $sep = '-'): string
    {
        return Str::slugify($str, $sep);
    }
}

if (!function_exists('str_is_mail')) {
    /**
     * Check whether a string is a valid email address.
     *
     * @param  string $email
     * @return bool
     */
    function str_is_mail(string $email): bool
    {
        return Str::isMail($email);
    }
}

if (!function_exists('str_uuid')) {
    /**
     * Generate a UUID string.
     *
     * @return string
     */
    function str_uuid(): string
    {
        return Str::uuid();
    }
}

if (!function_exists('str_is_domain')) {
    /**
     * Check whether a string is a valid domain name.
     *
     * @param  string $domain
     * @return bool
     * @throws Exception
     */
    function str_is_domain(string $domain): bool
    {
        return Str::isDomain($domain);
    }
}

if (!function_exists('str_is_slug')) {
    /**
     * Check whether a string is a valid slug.
     *
     * @param  string $slug
     * @return string
     */
    function str_is_slug(string $slug): string
    {
        return Str::isSlug($slug);
    }
}

if (!function_exists('str_is_alpha')) {
    /**
     * Check whether a string contains only alphabetic characters.
     *
     * @param  string $string
     * @return bool
     * @throws Exception
     */
    function str_is_alpha(string $string): bool
    {
        return Str::isAlpha($string);
    }
}

if (!function_exists('str_is_lower')) {
    /**
     * Check whether a string is entirely lower-case.
     *
     * @param  string $string
     * @return bool
     */
    function str_is_lower(string $string): bool
    {
        return Str::isLower($string);
    }
}

if (!function_exists('str_is_upper')) {
    /**
     * Check whether a string is entirely upper-case.
     *
     * @param  string $string
     * @return bool
     */
    function str_is_upper(string $string): bool
    {
        return Str::isUpper($string);
    }
}

if (!function_exists('str_is_alpha_num')) {
    /**
     * Check whether a string is alphanumeric.
     *
     * @param  string $slug
     * @return bool
     * @throws Exception
     */
    function str_is_alpha_num(string $slug): bool
    {
        return Str::isAlphaNum($slug);
    }
}

if (!function_exists('str_shuffle_words')) {
    /**
     * Randomly shuffle the words of a string.
     *
     * @param  string $words
     * @return string
     */
    function str_shuffle_words(string $words): string
    {
        return Str::shuffleWords($words);
    }
}

if (!function_exists('str_wordily')) {
    /**
     * Split a string into an array of its words.
     *
     * @param  string $words String to split
     * @param  string $sep   Separator to split on
     * @return array
     */
    function str_wordily(string $words, string $sep = ''): array
    {
        return Str::wordily($words, $sep);
    }
}

if (!function_exists('str_plural')) {
    /**
     * Pluralise a word.
     *
     * @param  string $slug
     * @return string
     */
    function str_plural(string $slug): string
    {
        return Str::plural($slug);
    }
}

if (!function_exists('str_camel')) {
    /**
     * Convert a string to camelCase.
     *
     * @param  string $slug
     * @return string
     */
    function str_camel(string $slug): string
    {
        return Str::camel($slug);
    }
}

if (!function_exists('str_snake')) {
    /**
     * Convert a string to snake_case.
     *
     * @param  string $slug
     * @return string
     */
    function str_snake(string $slug): string
    {
        return Str::snake($slug);
    }
}

if (!function_exists('str_contains')) {
    /**
     * Check whether a string contains another string.
     *
     * @param  string $search Needle to look for
     * @param  string $string Haystack to search in
     * @return bool
     */
    function str_contains(string $search, string $string): bool
    {
        return Str::contains($search, $string);
    }
}

if (!function_exists('str_capitalize')) {
    /**
     * Capitalise a string.
     *
     * @param  string $slug
     * @return string
     */
    function str_capitalize(string $slug): string
    {
        return Str::capitalize($slug);
    }
}

if (!function_exists('str_random')) {
    /**
     * Generate a random string.
     *
     * @param  string $string Length or seed forwarded to Str::random()
     * @return string
     */
    function str_random(string $string): string
    {
        return Str::random($string);
    }
}

if (!function_exists('str_force_in_utf8')) {
    /**
     * Force string output to UTF-8 globally.
     *
     * @return void
     */
    function str_force_in_utf8(): void
    {
        Str::forceInUTF8();
    }
}

if (!function_exists('str_fix_utf8')) {
    /**
     * Repair a malformed UTF-8 string.
     *
     * @param  string $string
     * @return string
     */
    function str_fix_utf8(string $string): string
    {
        return Str::fixUTF8($string);
    }
}

if (!function_exists('app_db_seed')) {
    /**
     * Seed data programmatically.
     *
     * When `$name` is a Model class, `$data` is inserted into its table.
     * Otherwise `$name` is resolved to a seeder file whose returned map of
     * table => rows is inserted (each row merged with `$data`).
     *
     * @param  string $name Model class name or seeder file name
     * @param  array  $data Rows to insert, or values merged into each row
     * @return int|array Affected rows, or one result per seeded table
     * @throws ErrorException When the seeder file cannot be found
     */
    function app_db_seed(string $name, array $data = []): int|array
    {
        // A model class: insert straight into its table.
        if (class_exists($name)) {
            $instance = app($name);

            if ($instance instanceof Model) {
                $table = $instance->getTable();
                return DB::table($table)->insert($data);
            }
        }

        $filename = rtrim(config('app.seeder_path'), '/') . '/' . $name . '.php';

        if (!file_exists($filename)) {
            throw new ErrorException('[' . $name . '] seeder file not found');
        }

        $seeds = include $filename;
        $seeds = array_merge($seeds, []);
        $collections = [];

        // Each entry maps a table (or model class) to its rows.
        foreach ($seeds as $table => $payload) {
            if (class_exists($table)) {
                $instance = app($table);
                if ($instance instanceof Model) {
                    $table = $instance->getTable();
                }
            }
            $payload = array_merge($data, $payload);
            $collections[] = DB::table($table)->insert($payload);
        }

        return $collections;
    }
}

if (!function_exists('is_blank')) {
    /**
     * Determine if the given value is "blank".
     *
     * Null, an empty/whitespace string, and an empty Countable are blank;
     * numbers and booleans never are.
     *
     * @param  mixed $value
     * @return bool
     */
    function is_blank(mixed $value): bool
    {
        if (is_null($value)) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_numeric($value) || is_bool($value)) {
            return false;
        }

        if ($value instanceof Countable) {
            return count($value) === 0;
        }

        return empty($value);
    }
}

if (!function_exists("queue")) {
    /**
     * Push a task onto the queue.
     *
     * @param  QueueTask $producer Task to enqueue
     * @return void
     */
    function queue(QueueTask $producer): void
    {
        app("queue")->push($producer);
    }
}
