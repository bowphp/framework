<?php

use Bow\Auth\Auth;
use Bow\Mail\Mail;
use Bow\Event\Event;
use Bow\Support\Env;
use Bow\Support\Util;
use Bow\Security\Hash;
use Bow\Session\Cookie;
use Bow\Session\Session;
use Bow\Storage\Storage;
use Bow\Container\Capsule;
use Bow\Security\Tokenize;
use Bow\Support\Collection;
use Bow\Translate\Translator;
use Bow\Queue\ProducerService;
use Bow\Database\Database as DB;
use Bow\Http\Exception\HttpException;
use Bow\Http\Redirect;

if (!function_exists('app')) {
    /**
     * Application container
     *
     * @param  string|null  $key
     * @param  array $setting
     * @return \Bow\Support\Capsule|mixed
     */
    function app($key = null, array $setting = [])
    {
        $capsule = Capsule::getInstance();

        if ($key == null && $setting == null) {
            return $capsule;
        }

        if (count($setting) == 0) {
            return $capsule->make($key);
        }

        return $capsule->makeWith($key, $setting);
    }
}

if (!function_exists('config')) {
    /**
     * Application configuration
     *
     * @param  string|array $key
     * @param  mixed        $setting
     * @return \Bow\Configuration\Loader|mixed
     * @throws
     */
    function config($key = null, $setting = null)
    {
        $config = \Bow\Configuration\Loader::getInstance();

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
     * Response object instance
     *
     * @return \Bow\Http\Response
     */
    function response()
    {
        return app('response');
    }
}

if (!function_exists('request')) {
    /**
     * Represents the Request class
     *
     * @return \Bow\Http\Request
     */
    function request()
    {
        return app('request');
    }
}

if (!function_exists('db')) {
    /**
     * Allows to connect to another database and return the instance of the DB
     *
     * @param string  $name
     * @param callable $cb
     * @return DB
     * @throws
     */
    function db(string $name = null, callable $cb = null)
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

        // When callback is define, we execute the callback
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
     * View alias of View::parse
     *
     * @param string    $template
     * @param array|int $data
     * @param int       $code
     * @return mixed
     */
    function view(string $template, $data = [], $code = 200)
    {
        if (is_int($data)) {
            $code = $data;

            $data = [];
        }

        response()
            ->status($code);

        return Bow\View\View::parse($template, $data);
    }
}

if (!function_exists('table')) {
    /**
     * Table alias of DB::table
     *
     * @param  string $name
     * @param  string $connexion
     * @return Bow\Database\QueryBuilder
     */
    function table(string $name, string $connexion = null)
    {
        if (is_string($connexion)) {
            db($connexion);
        }

        return DB::table($name);
    }
}

if (!function_exists('get_last_insert_id')) {
    /**
     * Returns the last ID following an INSERT query
     * on a table whose ID is auto_increment.
     *
     * @param  string $name
     * @return int
     */
    function get_last_insert_id(string $name = null)
    {
        return DB::lastInsertId($name);
    }
}

if (!function_exists('db_select')) {
    /**
     * Launches SELECT SQL Queries
     *
     * db_select('SELECT * FROM users');
     *
     * @param string   $sql
     * @param array    $data
     * @return int|array|stdClass
     */
    function db_select(string $sql, array $data = [])
    {
        return DB::select($sql, $data);
    }
}

if (!function_exists('db_select_one')) {
    /**
     * Launches SELECT SQL Queries
     *
     * @param string   $sql
     * @param array    $data
     * @return int|array|StdClass
     */
    function db_select_one(string $sql, array $data = [])
    {
        return DB::selectOne($sql, $data);
    }
}

if (!function_exists('db_insert')) {
    /**
     * Launches INSERT SQL Queries
     *
     * @param string   $sql
     * @param array    $data
     * @return int
     */
    function db_insert(string $sql, array $data = [])
    {
        return DB::insert($sql, $data);
    }
}

if (!function_exists('db_delete')) {
    /**
     * Launches DELETE type SQL queries
     *
     * @param string   $sql
     * @param array    $data
     * @return int
     */
    function db_delete(string $sql, $data = [])
    {
        return DB::delete($sql, $data);
    }
}

if (!function_exists('db_update')) {
    /**
     * Launches UPDATE SQL Queries
     *
     * @param string $sql
     * @param array  $data
     * @return int
     */
    function db_update(string $sql, array $data = [])
    {
        return DB::update($sql, $data);
    }
}

if (!function_exists('db_statement')) {
    /**
     * Launches CREATE TABLE, ALTER TABLE, RENAME, DROP TABLE SQL Query
     *
     * @param string $sql
     * @return int
     */
    function db_statement($sql)
    {
        return DB::statement($sql);
    }
}

if (!function_exists('debug')) {
    /**
     * debug, variable debug function
     * it allows you to have a color
     * Synthaxic data types.
     *
     * @return void
     */
    function debug()
    {
        array_map(function ($x) {
            call_user_func_array([Util::class, 'debug'], [$x]);
        }, secure(func_get_args()));
    }
}

if (!function_exists("sep")) {
    /**
     * Get the PHP OS separator
     *
     * @return string
     */
    function sep()
    {
        return call_user_func([Util::class, 'sep']);
    }
}

if (!function_exists('create_csrf_token')) {
    /**
     * Create a new token
     *
     * @param  int $time
     * @return ?array
     */
    function create_csrf_token(int $time = null): ?array
    {
        return Tokenize::csrf($time);
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Get the generate token
     *
     * @return string
     */
    function csrf_token(): string
    {
        $csrf = create_csrf_token();

        return $csrf['token'];
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Get the input csrf field
     *
     * @return string
     */
    function csrf_field(): string
    {
        $csrf = create_csrf_token();

        return $csrf['field'];
    }
}

if (!function_exists('method_field')) {
    /**
     * Create hidden http method field
     *
     * @param  string $method
     * @return string
     */
    function method_field($method): string
    {
        $method = strtoupper($method);

        return '<input type="hidden" name="_method" value="' . $method . '">';
    }
}

if (!function_exists('generate_token_csrf')) {
    /**
     * Generate token string
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
     * Check the token value
     *
     * @param  string $token
     * @param  bool   $strict
     * @return bool
     */
    function verify_csrf(string $token, bool $strict = false): bool
    {
        return Tokenize::verify($token, $strict);
    }
}

if (!function_exists('csrf_time_is_expired')) {
    /**
     * Check if token is expired by time
     *
     * @param  string $time
     * @return bool
     */
    function csrf_time_is_expired(string $time = null): bool
    {
        return Tokenize::csrfExpired($time);
    }
}

if (!function_exists('json')) {
    /**
     * Make json response
     *
     * @param  array|object $data
     * @param  int   $code
     * @param  array $headers
     * @return string
     */
    function json(array|object $data, int $code = 200, array $headers = []): string
    {
        return response()->json($data, $code, $headers);
    }
}

if (!function_exists('download')) {
    /**
     * Download file
     *
     * @param string      $file
     * @param null|string $filename
     * @param array       $headers
     * @return string
     */
    function download(string $file, ?string $filename = null, array $headers = []): string
    {
        return response()->download($file, $filename, $headers);
    }
}

if (!function_exists('set_status_code')) {
    /**
     * Set status code
     *
     * @param  int $code
     * @return mixed
     */
    function set_status_code(int $code): mixed
    {
        return response()->status($code);
    }
}

if (!function_exists('sanitize')) {
    /**
     * Sanitize data
     *
     * @param  mixed $data
     * @return mixed
     */
    function sanitize(mixed $data): mixed
    {
        if (is_numeric($data)) {
            return $data;
        }

        return \Bow\Security\Sanitize::make($data);
    }
}

if (!function_exists('secure')) {
    /**
     * Secure data with sanitaze it
     *
     * @param  mixed $data
     * @return mixed
     */
    function secure(mixed $data): mixed
    {
        if (is_numeric($data)) {
            return $data;
        }

        return \Bow\Security\Sanitize::make($data, true);
    }
}

if (!function_exists('set_header')) {
    /**
     * Update http headers
     *
     * @param string $key
     * @param string $value
     * @return void
     */
    function set_header(string $key, string $value): void
    {
        response()->addHeader($key, $value);
    }
}

if (!function_exists('get_header')) {
    /**
     * Get http header
     *
     * @param  string $key
     * @return string|null
     */
    function get_header(string $key): ?string
    {
        return request()->getHeader($key);
    }
}

if (!function_exists('redirect')) {
    /**
     * Make redirect response
     *
     * @param  string $path
     * @return Redirect
     */
    function redirect(string $path = null): Redirect
    {
        $redirect = \Bow\Http\Redirect::getInstance();

        if ($path !== null) {
            $redirect->to($path);
        }

        return $redirect;
    }
}

if (!function_exists('curl')) {
    /**
     * Curl help
     *
     * @param  string $method
     * @param  string $url
     * @param  array  $payload
     * @param  bool   $return
     * @param  array $headers
     * @return array|null
     */
    function curl(string $method, string $url, array $payload = [], array $headers = [], $return = false)
    {
        $method = strtoupper($method);
        $options = [];

        if (!in_array($method, ['GET'])) {
            $options['CURLOPT_POSTFIELDS'] = http_build_query($payload);
            $options['CURLOPT_POST'] = 1;
        }

        $ch = curl_init($url);

        if ($return == true) {
            if (!curl_setopt($ch, CURLOPT_RETURNTRANSFER, true)) {
                curl_close($ch);
                return null;
            }
        }

        // Set curl option
        curl_setopt_array($ch, $options);

        // Execute curl
        $data = curl_exec($ch);
        curl_close($ch);

        return $data;
    }
}

if (!function_exists('url')) {
    /**
     * Build url
     *
     * @param string|null $url
     * @param array       $parameters
     * @return string
     */
    function url(string $url = null, array $parameters = [])
    {
        $current = trim(request()->url(), '/');

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
     * Get database PDO instance
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
     * Set PDO instance
     *
     * @param  PDO $pdo
     * @return PDO
     */
    function set_pdo(PDO $pdo): PDO
    {
        DB::setPdo($pdo);

        return pdo();
    }
}

if (!function_exists('collect')) {

    /**
     * Create new Ccollection instance
     *
     * @param  array $data
     * @return \Bow\Support\Collection
     */
    function collect(array $data = []): \Bow\Support\Collection
    {
        return new Collection($data);
    }
}

if (!function_exists('encrypt')) {
    /**
     * Encrypt data
     *
     * @param  string $data
     * @return string
     */
    function encrypt(string $data): string
    {
        return \Bow\Security\Crypto::encrypt($data);
    }
}

if (!function_exists('decrypt')) {
    /**
     * Decrypt data
     *
     * @param  string $data
     * @return string
     */
    function decrypt(string $data): string
    {
        return \Bow\Security\Crypto::decrypt($data);
    }
}

if (!function_exists('db_transaction')) {
    /**
     * Start Database transaction
     *
     * @param callable $cb
     * @return void
     */
    function db_transaction(callable $cb = null): void
    {
        DB::startTransaction($cb);
    }
}

if (!function_exists('db_transaction_started')) {
    /**
     * Check if database transaction
     *
     * @return bool
     */
    function db_transaction_started(): bool
    {
        return DB::getPdo()->inTransaction();
    }
}

if (!function_exists('db_rollback')) {
    /**
     * Stop database transaction
     *
     * @return void
     */
    function db_rollback(): void
    {
        DB::rollback();
    }
}

if (!function_exists('db_commit')) {
    /**
     * Commit request after transaction
     *
     * @return void
     */
    function db_commit(): void
    {
        DB::commit();
    }
}

if (!function_exists('event')) {
    /**
     * Event event
     *
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

if (!function_exists('flash')) {
    /**
     * Flash session
     *
     * @param string $key
     * @param string $message
     * @return mixed
     */
    function flash(string $key, string $message)
    {
        return Session::getInstance()
            ->flash($key, $message);
    }
}

if (!function_exists('email')) {
    /**
     * Send email
     *
     * @param null|string $view
     * @param array       $data
     * @param callable    $cb
     * @return \Bow\Mail\Contracts\MailDriverInterface|bool
     * @throws
     */
    function email(
        string $view = null,
        array $data = [],
        callable $cb = null
    ): \Bow\Mail\Contracts\MailDriverInterface|bool {
        if ($view === null) {
            return Mail::getInstance();
        }

        return Mail::send($view, $data, $cb);
    }
}

if (!function_exists('raw_email')) {
    /**
     * Send raw email
     *
     * @param  array $to
     * @param  string       $subject
     * @param  string       $message
     * @param  array        $headers
     * @return bool
     */
    function raw_email(string $to, string $subject, string $message, array $headers = []): bool
    {
        return Mail::raw($to, $subject, $message, $headers);
    }
}

if (!function_exists('session')) {
    /**
     * Session help
     *
     * @param  string $value
     * @param  mixed $default
     * @return mixed
     */
    function session(string $value = null, mixed $default = null): mixed
    {
        if ($value == null) {
            return Session::getInstance();
        }

        if (!is_array($value)) {
            return Session::getInstance()->get($value, $default);
        }

        foreach ($value as $key => $item) {
            Session::getInstance()->add($key, $item);
        }

        return $value;
    }
}

if (!function_exists('cookie')) {
    /**
     * Cooke alias
     *
     * @param  string   $key
     * @param  mixed    $data
     * @param  int      $expirate
     * @param  string   $path
     * @param  string   $domain
     * @param  bool     $secure
     * @param  bool     $http
     * @return null|string
     */
    function cookie(
        string $key = null,
        mixed $data = null,
        int $expirate = 3600,
        string $path = null,
        string $domain = null,
        bool $secure = false,
        bool $http = true
    ) {
        if ($key === null) {
            return Cookie::all();
        }

        if ($key !== null && $data == null) {
            return Cookie::get($key);
        }

        if ($key !== null && $data !== null) {
            return Cookie::set($key, $data, $expirate, $path, $domain, $secure, $http);
        }

        return null;
    }
}

if (!function_exists('validator')) {
    /**
     * Validate the information on the well-defined criterion
     *
     * @param  array $inputs
     * @param  array $rules
     * @return \Bow\Validation\Validate
     */
    function validator(array $inputs, array $rules): \Bow\Validation\Validate
    {
        return \Bow\Validation\Validator::make($inputs, $rules);
    }
}

if (!function_exists('route')) {
    /**
     * Get Route by name
     *
     * @param  string $name
     * @param  array  $data
     * @param  bool  $absolute
     * @return string
     */
    function route(string $name, array $data = [], $absolute = false)
    {
        $routes = config('app.routes');

        if (is_bool($data)) {
            $absolute = $data;
            $data = [];
        }

        if (!isset($routes[$name])) {
            throw new \InvalidArgumentException(
                'The route named ' . $name . ' does not define.',
                E_USER_ERROR
            );
        }

        $url = $routes[$name];

        if (preg_match_all('/(?::([a-zA-Z0-9_-]+\??))/', $url, $matches)) {
            $keys = end($matches);
            foreach ($keys as $key) {
                if (preg_match("/\?$/", $key)) {
                    $valide_key = trim($key, "?");
                    $value = $data[$valide_key] ?? "";
                    unset($data[$valide_key]);
                } else {
                    if (!isset($data[$key])) {
                        throw new InvalidArgumentException(
                            "The $key key is not provide"
                        );
                    }
                    $value = $data[$key];
                    unset($data[$key]);
                }
                $url = str_replace(':' . $key, $value, $url);
                $url = str_replace("//", "/", $url);
            }
        }

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
     * Escape the HTML tags in the chain.
     *
     * @param  string $value
     * @return string
     */
    function e(string $value): string
    {
        return htmlentities($value, ENT_QUOTES, 'UTF-8', false);
    }
}

if (!function_exists('storage_service')) {
    /**
     * Service loader
     *
     * @return \Bow\Storage\Service\FTPService|\Bow\Storage\Service\S3Service
     */
    function storage_service(string $service)
    {
        return Storage::service($service);
    }
}

if (!function_exists('mount')) {
    /**
     * Alias on the mount method
     *
     * @param string $mount
     * @return \Bow\Storage\Service\DiskFilesystemService
     * @throws \Bow\Storage\Exception\ResourceException
     */
    function mount(string $mount): \Bow\Storage\Service\DiskFilesystemService
    {
        return Storage::mount($mount);
    }
}

if (!function_exists('file_system')) {
    /**
     * Alias on the mount method
     *
     * @param string $mount
     * @return \Bow\Storage\Service\DiskFilesystemService
     * @throws \Bow\Storage\Exception\ResourceException
     */
    function file_system(string $mount): \Bow\Storage\Service\DiskFilesystemService
    {
        return mount($mount);
    }
}

if (!function_exists('cache')) {
    /**
     * Cache help
     *
     * @param  string $key
     * @param  mixed  $value
     * @param  int  $ttl
     * @return mixed
     */
    function cache(string $key = null, mixed $value = null, int $ttl = null)
    {
        if ($key !== null && $value === null) {
            return \Bow\Cache\Cache::get($key);
        }

        return \Bow\Cache\Cache::add($key, $value, $ttl);
    }
}

if (!function_exists('redirect_back')) {
    /**
     * Make redirection to back
     *
     * @param int $status
     * @return Bow\Http\Redirect
     */
    function redirect_back(int $status = 302): \Bow\Http\Redirect
    {
        return redirect()->back($status);
    }
}

if (!function_exists('app_hash')) {
    /**
     * Alias on the class Hash.
     *
     * @param  string $data
     * @param  mixed  $hash_value
     * @return bool|string
     */
    function app_hash(string $data, string $hash_value = null): bool|string
    {
        if (!is_null($hash_value)) {
            return Hash::check($data, $hash_value);
        }

        return Hash::make($data);
    }
}

if (!function_exists('bow_hash')) {
    /**
     * Alias on the class Hash.
     *
     * @deprecated
     * @param  string $data
     * @param  mixed  $hash_value
     * @return bool|string
     */
    function bow_hash(string $data, string $hash_value = null): bool|string
    {
        return app_hash($data, $hash_value);
    }
}

if (!function_exists('trans')) {
    /**
     * Make translation
     *
     * @param string $key
     * @param array $data
     * @param bool $choose
     * @return string|Bow\Translate\Translator
     */
    function trans(
        string $key = null,
        array $data = [],
        bool $choose = false
    ): string|Bow\Translate\Translator {
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
     * Alias of trans
     *
     * @param  string $key
     * @param  array $data
     * @param  bool $choose
     * @return string
     */
    function t(
        string $key,
        array $data = [],
        bool $choose = false
    ): string|Bow\Translate\Translator {
        return trans($key, $data, $choose);
    }
}

if (!function_exists('__')) {
    /**
     * Alias of trans
     *
     * @param  $key
     * @param  $data
     * @param  bool $choose
     * @return string
     */
    function __(
        string $key,
        array $data = [],
        bool $choose = false
    ): string|Bow\Translate\Translator {
        return trans($key, $data, $choose);
    }
}

if (!function_exists('app_env')) {
    /**
     * Gets the app environement variable
     *
     * @param string $key
     * @param mixed $default
     * @return string
     */
    function app_env(string $key, mixed $default = null)
    {
        if (Env::isLoaded()) {
            return Env::get($key, $default);
        }

        return $default;
    }
}

if (!function_exists('app_assets')) {
    /**
     * Gets the app assets
     *
     * @param string $filename
     * @return string
     */
    function app_assets(string $filename): string
    {
        return rtrim(app_env("APP_ASSET_PREFIX", "/"), "/") . "/" . trim($filename, "/");
    }
}

if (!function_exists('app_abort')) {
    /**
     * Abort bow execution
     *
     * @param int    $code
     * @param string $message
     * @return \Bow\Http\Response
     * @throws HttpException
     */
    function app_abort(int $code = 500, string $message = '')
    {
        throw new HttpException($message, $code);
    }
}

if (!function_exists('app_abort_if')) {
    /**
     * Abort bow execution if condiction is true
     *
     * @param boolean $boolean
     * @param int $code
     * @param string $message
     * @return \Bow\Http\Response|null
     */
    function app_abort_if(
        bool $boolean,
        int $code,
        string $message = ''
    ): \Bow\Http\Response|null {
        if ($boolean) {
            return app_abort($code, $message);
        }

        return null;
    }
}

if (!function_exists('app_mode')) {
    /**
     * Get app enviroment mode
     *
     * @return string
     */
    function app_mode(): string
    {
        return strtolower(app_env('APP_ENV'));
    }
}

if (!function_exists('client_locale')) {
    /**
     * Get client request language
     *
     * @return string
     */
    function client_locale(): string
    {
        return request()->lang();
    }
}

if (!function_exists('old')) {
    /**
     * Get old request valude
     *
     * @param string $key
     * @param mixed $fullback
     * @return mixed
     */
    function old(string $key, mixed $fullback = null): mixed
    {
        return request()->old($key, $fullback);
    }
}

if (!function_exists('auth')) {
    /**
     * Recovery of the guard
     *
     * @param string $guard
     * @return \Bow\Auth\Guards\GuardContract
     * @throws
     */
    function auth(string $guard = null): \Bow\Auth\Guards\GuardContract
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
     * Log error message
     *
     * @param string $level
     * @param string $message
     * @param array $context
     * @return Monolog
     */
    function logger(?string $level, ?string $message, array $context = [])
    {
        if (is_null($level)) {
            return app('logger');
        }

        if (!in_array($level, ['info', 'warning', 'error', 'critical', 'debug'])) {
            return false;
        }

        return app('logger')->$level($message, $context);
    }
}

if (!function_exists('str_slug')) {
    /**
     * Slugify
     *
     * @param  string $str
     * @param  string $sep
     * @return string
     */
    function str_slug(string $str, string $sep = '-'): string
    {
        return \Bow\Support\Str::slugify($str, $sep);
    }
}

if (!function_exists('str_is_mail')) {
    /**
     * Check if the email is valid
     *
     * @param string $email
     * @return bool
     */
    function str_is_mail(string $email): bool
    {
        return \Bow\Support\Str::isMail($email);
    }
}

if (!function_exists('str_is_domain')) {
    /**
     * Check if the string is domain
     *
     * @param string $domain
     * @return bool
     * @throws
     */
    function str_is_domain(string $domain): bool
    {
        return \Bow\Support\Str::isDomain($domain);
    }
}

if (!function_exists('str_is_slug')) {
    /**
     * Check if string is slug
     *
     * @param string $slug
     * @return bool
     * @throws
     */
    function str_is_slug(string $slug): string
    {
        return \Bow\Support\Str::isSlug($slug);
    }
}

if (!function_exists('str_is_alpha')) {
    /**
     * Check if the string is alpha
     *
     * @param string $string
     * @return bool
     * @throws
     */
    function str_is_alpha(string $string): bool
    {
        return \Bow\Support\Str::isAlpha($string);
    }
}

if (!function_exists('str_is_lower')) {
    /**
     * Check if the string is lower
     *
     * @param string $string
     * @return bool
     */
    function str_is_lower(string $string): bool
    {
        return \Bow\Support\Str::isLower($string);
    }
}

if (!function_exists('str_is_upper')) {
    /**
     * Check if the string is upper
     *
     * @param string $string
     * @return bool
     */
    function str_is_upper(string $string): bool
    {
        return \Bow\Support\Str::isUpper($string);
    }
}

if (!function_exists('str_is_alpha_num')) {
    /**
     * Check if string is alpha numeric
     *
     * @param string $slug
     * @return bool
     * @throws
     */
    function str_is_alpha_num(string $slug): bool
    {
        return \Bow\Support\Str::isAlphaNum($slug);
    }
}

if (!function_exists('str_shuffle_words')) {
    /**
     * Shuffle words
     *
     * @param string $words
     * @return string
     */
    function str_shuffle_words(string $words): string
    {
        return \Bow\Support\Str::shuffleWords($words);
    }
}

if (!function_exists('str_wordify')) {
    /**
     * Return the array contains the word of the passed string
     *
     * @param string $words
     * @param string $sep
     * @return array
     */
    function str_wordify(string $words, string $sep = ''): array
    {
        return \Bow\Support\Str::wordify($words, $sep);
    }
}

if (!function_exists('str_plurial')) {
    /**
     * Transform text to plurial
     *
     * @param string $slug
     * @return string
     */
    function str_plurial(string $slug): string
    {
        return \Bow\Support\Str::plurial($slug);
    }
}

if (!function_exists('str_camel')) {
    /**
     * Transform text to camel case
     *
     * @param string $slug
     * @return string
     */
    function str_camel($slug): string
    {
        return \Bow\Support\Str::camel($slug);
    }
}

if (!function_exists('str_snake')) {
    /**
     * Transform text to snake case
     *
     * @param string $slug
     * @return string
     */
    function str_snake(string $slug): string
    {
        return \Bow\Support\Str::snake($slug);
    }
}

if (!function_exists('str_contains')) {
    /**
     * Check if string contain an other string
     *
     * @param string $search
     * @param string $string
     * @return bool
     */
    function str_contains(string $search, string $string): bool
    {
        return \Bow\Support\Str::contains($search, $string);
    }
}

if (!function_exists('str_capitalize')) {
    /**
     * Capitalize
     *
     * @param string $slug
     * @return string
     */
    function str_capitalize(string $slug): string
    {
        return \Bow\Support\Str::capitalize($slug);
    }
}

if (!function_exists('str_random')) {
    /**
     * Random string
     *
     * @param string $string
     * @return string
     */
    function str_random(string $string): string
    {
        return \Bow\Support\Str::randomize($string);
    }
}

if (!function_exists('str_force_in_utf8')) {
    /**
     * Force output string to utf8
     *
     * @return void
     */
    function str_force_in_utf8(): void
    {
        \Bow\Support\Str::forceInUTF8();
    }
}

if (!function_exists('str_fix_utf8')) {
    /**
     * Force output string to utf8
     *
     * @param string $string
     * @return string
     */
    function str_fix_utf8(string $string): string
    {
        return \Bow\Support\Str::fixUTF8($string);
    }
}

if (!function_exists('db_seed')) {
    /**
     * Make programmatic seeding
     *
     * @param string $name
     * @param array $data
     * @return mixed
     */
    function db_seed(string $name, array $data = []): mixed
    {
        if (class_exists($name, true)) {
            $instance = app($name);
            if ($instance instanceof \Bow\Database\Barry\Model) {
                $table = $instance->getTable();
                return DB::table($table)->insert($data);
            }
        }

        $filename = rtrim(config('app.seeder_path'), '/') . '/' . $name . '_seeder.php';

        if (!file_exists($filename)) {
            throw new \ErrorException('[' . $name . '] seeder file not found');
        }

        $seeds = require $filename;
        $seeds = array_merge($seeds, []);
        $collections = [];

        foreach ($seeds as $table => $payload) {
            if (class_exists($table, true)) {
                $instance = app($table);
                if ($instance instanceof \Bow\Database\Barry\Model) {
                    $table = $instance->getTable();
                }
            }
            $payload = array_merge($data, $payload);
            $collections[] = DB::table($table)->insert($payload);
        }

        return $collections;
    }
}

if (! function_exists('is_blank')) {
    /**
     * Determine if the given value is "blank".
     *
     * @param  mixed  $value
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
     * Push the producer on queue
     *
     * @param ProducerService $producer
     */
    function queue(ProducerService $producer): void
    {
        app("queue")->push($producer);
    }
}
