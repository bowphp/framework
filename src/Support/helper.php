<?php

use Bow\Auth\Auth;
use Bow\Container\Capsule;
use Bow\Database\Database as DB;
use Bow\Event\Event;
use Bow\Http\Exception\HttpException;
use Bow\Mail\Mail;
use Bow\Security\Hash;
use Bow\Security\Tokenize;
use Bow\Session\Cookie;
use Bow\Session\Session;
use Bow\Storage\Storage;
use Bow\Support\Collection;
use Bow\Support\Env;
use Bow\Support\Util;
use Bow\Translate\Translator;

if (!function_exists('app')) {
    /**
     * Application container
     *
     * @param  null  $key
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
     * @param  string $content
     * @param  int    $code
     * @return \Bow\Http\Response
     */
    function response($content = '', $code = 200)
    {
        $response = app('response');

        $response->status($code);

        if (is_null($content)) {
            return $response;
        }

        $response->setContent($content);

        return $response;
    }
}

if (!function_exists('request')) {
    /**
     * Represents the Request class
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
    function db($name = null, callable $cb = null)
    {
        if (func_num_args() == 0) {
            return DB::getInstance();
        }

        if (!is_string($name)) {
            throw new InvalidArgumentException(
                'Error on parameter 1. Expected string type.'
            );
        }

        $last_connection = DB::getConnectionName();

        if ($last_connection !== $name) {
            DB::connection($name);
        }

        if (is_callable($cb)) {
            return $cb();
        }

        return DB::connection($last_connection);
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
    function view($template, $data = [], $code = 200)
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
    function table($name, $connexion = null)
    {
        if (is_string($connexion)) {
            db($connexion);
        }

        return DB::table($name);
    }
}

if (!function_exists('last_insert_id')) {
    /**
     * Returns the last ID following an INSERT query
     * on a table whose ID is auto_increment.
     *
     * @param  string $name
     * @return int
     */
    function last_insert_id($name = null)
    {
        return DB::lastInsertId($name);
    }
}

if (!function_exists('select')) {
    /**
     * Launches SELECT SQL Queries
     *
     * select('SELECT * FROM users');
     *
     * @param string   $sql
     * @param array    $data
     * @return int|array|stdClass
     */
    function select($sql, $data = [])
    {
        return DB::select($sql, $data);
    }
}

if (!function_exists('select_one')) {
    /**
     * Launches SELECT SQL Queries
     *
     * @param string   $sql
     * @param array    $data
     * @return int|array|StdClass
     */
    function select_one($sql, $data = [])
    {
        return DB::selectOne($sql, $data);
    }
}

if (!function_exists('insert')) {
    /**
     * Launches INSERT SQL Queries
     *
     * @param string   $sql
     * @param array    $data
     * @return int
     */
    function insert($sql, array $data = [])
    {
        return DB::insert($sql, $data);
    }
}

if (!function_exists('delete')) {
    /**
     * Launches DELETE type SQL queries
     *
     * @param string   $sql
     * @param array    $data
     * @return int
     */
    function delete($sql, $data = [])
    {
        return DB::delete($sql, $data);
    }
}

if (!function_exists('update')) {
    /**
     * Launches UPDATE SQL Queries
     *
     * @param string $sql
     * @param array  $data
     * @return int
     */
    function update($sql, array $data = [])
    {
        return DB::update($sql, $data);
    }
}

if (!function_exists('statement')) {
    /**
     * Launches CREATE TABLE, ALTER TABLE, RENAME, DROP TABLE SQL Query
     *
     * @param string $sql
     * @return int
     */
    function statement($sql)
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

        die;
    }
}

if (!function_exists('create_csrf_token')) {
    /**
     * Create a new token
     *
     * @param  int $time [optional]
     * @return \stdClass
     */
    function create_csrf_token($time = null)
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
    function csrf_token()
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
    function csrf_field()
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
    function method_field($method)
    {
        $method = strtoupper($method);

        return '<input type="hidden" name="_method" value="'.$method.'">';
    }
}

if (!function_exists('generate_token_csrf')) {
    /**
     * Generate token string
     *
     * @return string
     */
    function gen_csrf_token()
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
     * @return string
     */
    function verify_csrf($token, $strict = false)
    {
        return Tokenize::verify($token, $strict);
    }
}

if (!function_exists('csrf_time_is_expired')) {
    /**
     * Check if token is expired by time
     *
     * @param  string $time
     * @return string
     */
    function csrf_time_is_expired($time = null)
    {
        return Tokenize::csrfExpired($time);
    }
}

if (!function_exists('json')) {
    /**
     * Make json response
     *
     * @param  mixed $data
     * @param  int   $code
     * @param  array $headers
     * @return mixed
     */
    function json($data, $code = 200, array $headers = [])
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
     * @param string      $disposition
     * @return string
     */
    function download($file, $filename = null, array $headers = [], $disposition = 'attachment')
    {
        return response()->download($file, $filename, $headers, $disposition);
    }
}

if (!function_exists('status_code')) {
    /**
     * Set status code
     *
     * @param  int $code
     * @return mixed
     */
    function status_code($code)
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
    function sanitize($data)
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
    function secure($data)
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
    function set_header($key, $value)
    {
        response()
            ->addHeader($key, $value);
    }
}

if (!function_exists('get_header')) {
    /**
     * Get http header
     *
     * @param  string $key
     * @return string|null
     */
    function get_header($key)
    {
        return request()
            ->getHeader($key);
    }
}

if (!function_exists('redirect')) {
    /**
     * Make redirect response
     *
     * @param  string|array $path
     * @return \Bow\Http\Redirect
     */
    function redirect($path = null)
    {
        $redirect = \Bow\Http\Redirect::getInstance();

        if ($path !== null) {
            $redirect->to($path);
        }

        return $redirect;
    }
}

if (!function_exists('send')) {
    /**
     * Send simple message to client
     *
     * @param  string $data
     * @return mixed
     */
    function send($data)
    {
        return response()->send($data);
    }
}

if (!function_exists('curl')) {
    /**
     * Curl help
     *
     * @param  string $method
     * @param  string $url
     * @param  array  $params
     * @param  bool   $return
     * @param  string $header
     * @return array|null
     */
    function curl($method, $url, array $params = [], $return = false, &$header = null)
    {
        $ch = curl_init($url);

        $options = [
            'CURLOPT_POSTFIELDS' => http_build_query($params)
        ];

        if ($return == true) {
            if (!curl_setopt($ch, CURLOPT_RETURNTRANSFER, true)) {
                curl_close($ch);

                return null;
            }
        }

        if ($method == 'POST') {
            $options['CURLOPT_POST'] = 1;
        }

        // Set curl option
        curl_setopt_array($ch, $options);

        // Execute curl
        $data = curl_exec($ch);

        if ($header !== null) {
            $header = curl_getinfo($ch);
        }

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
    function url($url = null, array $parameters = [])
    {
        $current = trim(request()->url(), '/');

        if (is_array($url)) {
            $parameters = $url;

            $url = '';
        }

        if (is_string($url)) {
            $current .= '/'.trim($url, '/');
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
    function pdo()
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
    function set_pdo(PDO $pdo)
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
    function collect(array $data = [])
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
    function encrypt($data)
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
    function decrypt($data)
    {
        return \Bow\Security\Crypto::decrypt($data);
    }
}

if (!function_exists('db_transaction')) {
    /**
     * Start Database transaction
     *
     * @param callable $cb
     */
    function db_transaction(callable $cb = null)
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
    function db_transaction_started()
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
    function db_rollback()
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
    function db_commit()
    {
        DB::commit();
    }
}

if (!function_exists('add_event')) {
    /**
     * Add event
     *
     * @param  string                $event
     * @param  callable|array|string $fn
     * @return Event;
     *
     * @throws \Bow\Event\EventException
     */
    function add_event($event, $fn)
    {
        if (!is_string($event)) {
            throw new \Bow\Event\EventException(
                'The first parameter must be a string.'
            );
        }

        return call_user_func_array(
            [emitter(), 'on'],
            [$event, $fn]
        );
    }
}

if (!function_exists('add_event_once')) {
    /**
     * Add once event
     *
     * @param  string                $event
     * @param  callable|array|string $fn
     * @return Event
     * @throws \Bow\Event\EventException
     */
    function add_event_once($event, $fn)
    {
        if (!is_string($event)) {
            throw new \Bow\Event\EventException(
                'The first parameter must be a string.'
            );
        }

        return call_user_func_array(
            [emitter(), 'once'],
            [$event, $fn]
        );
    }
}

if (!function_exists('add_transmisson_event')) {
    /**
     * Add transmission event
     *
     * @param  string       $event
     * @param  array|string $fn
     * @return Event
     * @throws \Bow\Event\EventException
     */
    function add_transmisson_event($event, $fn)
    {
        if (!is_string($event)) {
            throw new \Bow\Event\EventException(
                'The first parameter must be a string.'
            );
        }

        return call_user_func_array(
            [emitter(), 'onTransmission'],
            [$event, $fn]
        );
    }
}

if (!function_exists('emitter')) {
    /**
     * Event emitter
     *
     * @return Event
     */
    function emitter()
    {
        return Event::getInstance();
    }
}

if (!function_exists('emit_event')) {
    /**
     * Fire event
     *
     * @param  string $event
     * @return void
     * @throws \Bow\Event\EventException
     */
    function emit_event($event)
    {
        if (!is_string($event)) {
            throw new \Bow\Event\EventException(
                'The first parameter must be a string.'
            );
        }

        call_user_func_array(
            [emitter(), 'emit'],
            func_get_args()
        );
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
    function flash($key, $message)
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
     * @return \Bow\Mail\Driver\SimpleMail|\Bow\Mail\Driver\Smtp|bool
     * @throws
     */
    function email($view = null, $data = [], callable $cb = null)
    {
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
     * @return Mail|mixed
     */
    function raw_email($to, $subject, $message, array $headers = [])
    {
        return Mail::raw($to, $subject, $message, $headers);
    }
}

if (!function_exists('session')) {
    /**
     * Session help
     *
     * @param  mixed $value
     * @param  mixed $default
     * @return mixed
     */
    function session($value = null, $default = null)
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
        $key = null,
        $data = null,
        $expirate = 3600,
        $path = null,
        $domain = null,
        $secure = false,
        $http = true
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
    function validator(array $inputs, array $rules)
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
    function route($name, $data = [], $absolute = false)
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

        if (preg_match('/:/', $url)) {
            foreach ($data as $key => $value) {
                $url = str_replace(':' . $key, $value, $url);
            }
        } else {
            if (count($data) > 0) {
                $url = $url . '?' . http_build_query($data);
            }
        }

        if ($absolute) {
            return rtrim(app_env('APP_URL'), '/') . '/' . ltrim($url, '/');
        }

        return rtrim(app_env('APP_URI_PREFIX', '/'), '/') . '/' . ltrim($url, '/');
    }
}

if (!function_exists('e')) {
    /**
     * Escape the HTML tags in the chain.
     *
     * @param  string $value
     * @return string
     */
    function e($value)
    {
        return htmlentities($value, ENT_QUOTES, 'UTF-8', false);
    }
}

if (!function_exists('ftp')) {
    /**
     * Ftp Service loader
     * @return \Bow\Storage\Service\FTPService
     */
    function ftp()
    {
        return Storage::service('ftp');
    }
}

if (!function_exists('s3')) {
    /**
     * S3 Service loader.
     * @return \Bow\Storage\Service\S3Service
     */
    function s3()
    {
        return Storage::service('s3');
    }
}

if (!function_exists('mount')) {
    /**
     * Alias on the mount method
     *
     * @param string $mount
     * @return \Bow\Storage\MountFilesystem
     *
     * @throws \Bow\Storage\Exception\ResourceException
     */
    function mount($mount)
    {
        return Storage::mount($mount);
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
    function cache($key = null, $value = null, $ttl = null)
    {
        if ($key !== null && $value === null) {
            return \Bow\Cache\Cache::get($key);
        }

        return \Bow\Cache\Cache::add($key, $value, $ttl);
    }
}

if (!function_exists('back')) {
    /**
     * Make redirection to back
     *
     * @param int $status
     * @return Bow\Http\Redirect
     */
    function back($status = 302)
    {
        return redirect()->back($status);
    }
}

if (!function_exists('bhash')) {
    /**
     * Alias on the class Hash.
     *
     * @param  string $data
     * @param  mixed  $hash_value
     * @return mixed
     */
    function bhash($data, $hash_value = null)
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
     * @param  string $data
     * @param  mixed  $hash_value
     * @return mixed
     */
    function bow_hash($data, $hash_value = null)
    {
        if (!is_null($hash_value)) {
            return Hash::check($data, $hash_value);
        }

        return Hash::make($data);
    }
}

if (!function_exists('trans')) {
    /**
     * Make translation
     *
     * @param string $key
     * @param array $data
     * @param bool $choose
     * @return string | Bow\Translate\Translator
     */
    function trans($key = null, $data = [], $choose = false)
    {
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
     * @param  $key
     * @param  $data
     * @param  bool $choose
     * @return string
     */
    function t($key, $data = [], $choose = false)
    {
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
    function __($key, $data = [], $choose = false)
    {
        return trans($key, $data, $choose);
    }
}

if (!function_exists('app_env')) {
    /**
     * Gets the app environement variable
     *
     * @param $key
     * @param $default
     * @return string
     */
    function app_env($key, $default = null)
    {
        if (Env::isLoaded()) {
            return Env::get($key, $default);
        }

        return $default;
    }
}

if (!function_exists('abort')) {
    /**
     * Abort bow execution
     *
     * @param int    $code
     * @param string $message
     * @return \Bow\Http\Response
     */
    function abort($code = 500, $message = '')
    {
        throw new HttpException($message, $code);
    }
}

if (!function_exists('abort_if')) {
    /**
     * Abort bow execution if condiction is true
     *
     * @param boolean $boolean
     * @param int $code
     * @param string $message
     * @return \Bow\Http\Response|null
     */
    function abort_if($boolean, $code, $message = '')
    {
        if ($boolean) {
            return abort($code, $message);
        }

        return null;
    }
}

if (!function_exists('app_mode')) {
    /**
     * Get app enviroment mode
     * @return string
     */
    function app_mode()
    {
        return app_env('APP_ENV');
    }
}

if (!function_exists('client_locale')) {
    /**
     * Get client request language
     * @return string
     */
    function client_locale()
    {
        return request()->lang();
    }
}

if (!function_exists('old')) {
    /**
     * Get old request valude
     *
     * @param string $key
     * @return mixed
     */
    function old($key)
    {
        return request()->old($key);
    }
}

if (!function_exists('auth')) {
    /**
     * Recovery of the guard
     *
     * @param string $guard
     * @return Bow\Auth\GuardContract
     * @throws
     */
    function auth($guard = null)
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
     * @return bool
     */
    function logger($level, $message, array $context = [])
    {
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
    function str_slug($str, $sep = '-')
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
    function str_is_mail($email)
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
    function str_is_domain($domain)
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
    function str_is_slug($slug)
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
    function str_is_alpha($string)
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
    function str_is_lower($string)
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
    function str_is_upper($string)
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
    function str_is_alpha_num($slug)
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
    function str_shuffle_words($words)
    {
        return \Bow\Support\Str::shuffleWords($words);
    }
}

if (!function_exists('str_wordify')) {
    /**
     * Check if string is slug
     *
     * @param string $words
     * @param string $sep
     * @return array
     */
    function str_wordify($words, $sep = '')
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
    function str_plurial($slug)
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
    function str_camel($slug)
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
    function str_snake($slug)
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
    function str_contains($search, $string)
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
    function str_capitalize($slug)
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
    function str_random($string)
    {
        return \Bow\Support\Str::randomize($string);
    }
}

if (!function_exists('str_force_in_utf8')) {
    /**
     * Force output string to utf8
     *
     * @param string $string
     * @return void
     */
    function str_force_in_utf8($string)
    {
        return \Bow\Support\Str::forceInUTF8($string);
    }
}

if (!function_exists('seed')) {
    /**
     * Make programmatic seeding
     *
     * @param string $table
     * @return void
     */
    function seed($entry, array $data = [])
    {
        $filename = rtrim(config('app.seeder_path'), '/').'/'.$entry.'_seeder.php';

        if (!file_exists($filename)) {
            throw new \ErrorException('['.$entry.'] seeder file not found');
        }

        $seeds = require $filename;
        $collection = array_merge($seeds, []);

        if (count($data) > 0) {
            return DB::table($table)->insert($data);
        }

        foreach ($collection as $table => $seed) {
            if (class_exists($table, true)) {
                $instance = app($table);
                if ($instance instanceof \Bow\Database\Barry\Model) {
                    $table = $instance->getTable();
                }
            }
            DB::table($table)->insert($seed);
        }
    }
}
