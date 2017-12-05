<?php
/**
 * BOW HELPER
 * ==========
 * Définir des liens symbolique de l'ensemble de
 * fonctions de Bow
 */

use Bow\Mail\Mail;
use Bow\Http\Cache;
use Bow\Http\Input;
use Bow\Event\Event;
use Bow\Support\Env;
use Bow\Support\Util;
use Bow\Support\Faker;
use Bow\Security\Hash;
use Bow\Config\Config;
use Bow\Session\Cookie;
use Bow\Support\Capsule;
use Bow\Session\Session;
use Bow\Resource\Storage;
use Bow\Security\Tokenize;
use Bow\Support\Collection;
use Bow\Database\Database as DB;

if (!function_exists('app')) {
    /**
     * Application container
     *
     * @param  null  $key
     * @param  array $setting
     * @return \Bow\Support\Capsule|mixed
     */
    function app($key = null, $setting = [])
    {
        $capsule = Capsule::getInstance();

        if ($key == null && $setting == null) {
            return $capsule;
        }

        if ($setting == null) {
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
     * @return Config|mixed
     */
    function config($key = null, $setting = null)
    {
        app()->bind(
            'config',
            function () {
                return Config::singleton();
            }
        );

        $config = app('config');
        
        if (is_null($key)) {
            return $config;
        }

        return $config($key, $setting);
    }
}

if (!function_exists('response')) {
    /**
     * response, manipule une instance de Response::class
     *
     * @param  string $template, le message a envoyer
     * @param  int    $code,     le code d'erreur
     * @param  string $type,     le type mime du contenu
     * @return \Bow\Http\Response
     */
    function response($template = null, $code = 200, $type = 'text/html')
    {

        app()->bind(
            'response',
            function () {
                return new \Bow\Http\Response();
            }
        );

        $response = app('response');
        $response->statusCode($code);

        if (is_null($template)) {
            return $response;
        }

        $response->addHeader('Content-Type', $type);
        $response->send($template);

        return $response;
    }
}

if (!function_exists('request')) {
    /**
     * répresente le classe Request
     *
     * @return \Bow\Http\Request
     */
    function request()
    {
        app()->bind(
            'request',
            function () {
                return \Bow\Http\Request::singleton();
            }
        );

        return app('request');
    }
}

if (!function_exists('db')) {
    /**
     * permet de se connecter sur une autre base de donnée
     * et retourne l'instance de la DB
     *
     * @param string   $name le nom de la configuration de la db
     * @param callable $cb   la fonction de rappel
     *
     * @return DB, the DB reference
     */
    function db($name = null, callable $cb = null)
    {
        if (func_num_args() == 0) {
            return DB::instance();
        }

        if (!is_string($name)) {
            throw new InvalidArgumentException('Erreur sur le parametre 1. Type string attendu.');
        }

        $last_connection = DB::getConnectionName();

        if ($last_connection !== $name) {
            DB::connection($name);
        }

        if (is_callable($cb)) {
            return $cb();
        } else {
            return DB::connection($last_connection);
        }
    }
}

if (!function_exists('view')) {
    /**
     * view aliase sur Response::view
     *
     * @param string    $template
     * @param array|int $data
     * @param int       $code
     *
     * @return mixed
     */
    function view($template, $data = [], $code = 200)
    {
        if (is_int($data)) {
            $code = $data;
            $data = [];
        }
        response()->statusCode($code);
        return Bow\View\View::make($template, $data);
    }
}

if (!function_exists('table')) {
    /**
     * table aliase DB::table
     *
     * @param  string $name
     * @param  string $class.
     * @param  string $primary_key
     * @param  string $connexion
     * @return Bow\Database\Query\Builder
     */
    function table($name, $class = null, $primary_key = null, $connexion = null)
    {
        if (is_string($connexion)) {
            db($connexion);
        }
        return DB::table($name, $class, $primary_key);
    }
}

if (!function_exists('query_maker')) {
    /**
     * fonction d'astuce
     *
     * @param string   $sql
     * @param array    $data
     * @param callable $cb
     * @param $method
     *
     * @return mixed
     */
    function query_maker($sql, $data, $cb, $method)
    {
        $rs = null;

        if (is_callable($data)) {
            $cb = $data;
            $data = [];
        }

        if (method_exists(DB::class, $method)) {
            $rs = DB::$method($sql, $data);
        }

        if (is_callable($cb)) {
            return call_user_func_array($cb, [$rs]);
        }

        return $rs;
    }
}

if (!function_exists('last_insert_id')) {
    /**
     * Retourne le dernier ID suite a une requete INSERT sur un table dont ID est
     * auto_increment.
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
     * statement lance des requete SQL de type SELECT
     *
     * select('SELECT * FROM users');
     *
     * @param string   $sql
     * @param array    $data
     * @param callable $cb
     *
     * @return int|array|StdClass
     */
    function select($sql, $data = [], $cb = null)
    {
        return query_maker($sql, $data, $cb, 'select');
    }
}

if (!function_exists('select_one')) {
    /**
     * statement lance des requete SQL de type SELECT
     *
     * @param string   $sql
     * @param array    $data
     * @param callable $cb
     *
     * @return int|array|StdClass
     */
    function select_one($sql, $data = [], $cb = null)
    {
        return query_maker($sql, $data, $cb, 'selectOne');
    }
}

if (!function_exists('insert')) {
    /**
     * statement lance des requete SQL de type INSERT
     *
     * @param string   $sql
     * @param array    $data
     * @param callable $cb
     *
     * @return int
     */
    function insert($sql, array $data = [], $cb = null)
    {
        return query_maker($sql, $data, $cb, 'insert');
    }
}

if (!function_exists('delete')) {
    /**
     * statement lance des requete SQL de type DELETE
     *
     * @param string   $sql
     * @param array    $data
     * @param callable $cb
     *
     * @return int
     */
    function delete($sql, $data = [], $cb = null)
    {
        return query_maker($sql, $data, $cb, 'delete');
    }
}

if (!function_exists('update')) {
    /**
     * update lance des requete SQL de type UPDATE
     *
     * @param string   $sql
     * @param array    $data
     * @param callable $cb
     *
     * @return int
     */
    function update($sql, array $data = [], $cb = null)
    {
        return query_maker($sql, $data, $cb, 'update');
    }
}

if (!function_exists('statement')) {
    /**
     * statement lance des requete SQL de type CREATE TABLE|ALTER TABLE|RENAME|DROP TABLE
     *
     * @param string $sql
     *
     * @return int
     */
    function statement($sql)
    {
        return query_maker($sql, [], null, 'statement');
    }
}

if (!function_exists('slugify')) {
    /**
     * slugify, transforme un chaine de caractère en slug
     * eg. la chaine '58 comprendre bow framework' -> '58-comprendre-bow-framework'
     *
     * @param  string $str
     * @param  string $sperator
     * @return string
     */
    function slugify($str, $sperator = '-')
    {
        return \Bow\Support\Str::slugify($str, $sperator);
    }
}

if (!function_exists('str_slug')) {
    /**
     * slugify, transforme un chaine de caractère en slug
     * eg. la chaine '58 comprendre bow framework' -> '58-comprendre-bow-framework'
     *
     * @param  string $str
     * @param  string $sperator
     * @return string
     */
    function str_slug($str, $sperator = '-')
    {
        return slugify($str, $sperator);
    }
}

if (!function_exists('files')) {
    /**
     * files, fonction de type collection
     * manipule la variable global $_FILES
     *
     * @param  string $key
     * @return array|\Bow\Http\UploadFile
     */
    function files($key = null)
    {
        if ($key !== null) {
            return request()->file($key);
        }

        return request()->files();
    }
}

if (!function_exists('input')) {
    /**
     * input, fonction de type collection
     * manipule la variable global $_GET, $_POST, $_FILES
     *
     * @param  mixed $key
     * @return Input
     */
    function input($key = null)
    {
        $input = request()->input();

        if ($key === null) {
            return $input;
        }

        if ($input->has($key)) {
            return $input->get($key);
        }

        return null;
    }
}

if (!function_exists('debug')) {
    /**
     * debug, fonction de debug de variable
     * elle vous permet d'avoir un coloration
     * synthaxique des types de donnée.
     */
    function debug()
    {
        array_map(
            function ($x) {
                call_user_func_array([Util::class, 'debug'], [$x]);
            },
            secure(func_get_args())
        );
        die;
    }
}

if (!function_exists('create_csrf_token')) {
    /**
     * create_csrf, fonction permetant de récupérer le token généré
     *
     * @param  int $time [optional]
     * @return \StdClass
     */
    function create_csrf_token($time = null)
    {
        return Tokenize::csrf($time);
    }
}


if (!function_exists('csrf_token')) {
    /**
     * csrf_token, fonction permetant de récupérer le token généré
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
     * csrf_field, fonction permetant de récupérer un input généré
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
     * method_field, fonction permetant de récupérer un input généré
     *
     * @param  string $method
     * @return string
     */
    function method_field($method)
    {
        return '<input type="hidden" name="_method" value="'.$method.'">';
    }
}

if (!function_exists('generate_token_csrf')) {
    /**
     * csrf, fonction permetant de générer un token
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
     * verify_token_csrf, fonction permetant de vérifier un token
     *
     * @param  string $token  l'information sur le token
     * @param  bool   $strict vérifie le token et la date de création avec à la valeur
     *                        time()
     * @return string
     */
    function verify_csrf($token, $strict = false)
    {
        return Tokenize::verify($token, $strict);
    }
}

if (!function_exists('csrf_time_is_expirate')) {
    /**
     * csrf, fonction permetant de générer un token
     *
     * @param  string $time
     * @return string
     */
    function csrf_time_is_expirate($time = null)
    {
        return Tokenize::csrfExpirated($time);
    }
}

if (!function_exists('store')) {
    /**
     * store, effecture l'upload d'un fichier vers un repertoire
     *
     * @param  array    $file,     le fichier a
     *                             uploadé.
     * @param  $location
     * @param  $size
     * @param  array    $extension
     * @param  callable $cb
     * @return object
     */
    function store(array $file, $location, $size, array $extension, callable $cb = null)
    {

        if (is_int($location) || preg_match('/^([0-9]+)(m|k)$/', $location)) {
            $cb = $extension;
            $extension = $size;
            $size = $location;
            $location = storage_path();
        }

        return Storage::store($file, $location, $size, $extension, $cb);
    }
}

if (!function_exists('json')) {
    /**
     * json, permet de lance des reponses server de type json
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
     * download, permet de lancer le téléchargement d'un fichier.
     *
     * @param string      $file
     * @param null|string $name
     * @param array       $headers
     * @param string      $disposition
     */
    function download($file, $name = null, array $headers = [], $disposition = 'attachment')
    {
        return response()->download($file, $name, $headers, $disposition);
    }
}

if (!function_exists('status_code')) {
    /**
     * statuscode, permet de changer le code de la reponse du server
     *
     * @param  int $code=200
     * @return mixed
     */
    function status_code($code)
    {
        return response()->statusCode($code);
    }
}

if (!function_exists('sanitaze')) {
    /**
     * sanitaze, épure un variable d'information indésiration
     * eg. sanitaze('j\'ai') => j'ai
     *
     * @param  mixed $data
     * @return mixed
     */
    function sanitaze($data)
    {
        if (is_numeric($data)) {
            return $data;
        } else {
            return \Bow\Security\Sanitize::make($data);
        }
    }
}

if (!function_exists('secure')) {
    /**
     * secure, échape les anti-slashes, les balises html
     * eg. secure('j'ai') => j\'ai
     *
     * @param  mixed $data
     * @return mixed
     */
    function secure($data)
    {
        if (is_numeric($data)) {
            return $data;
        } else {
            return \Bow\Security\Sanitize::make($data, true);
        }
    }
}

if (!function_exists('set_header')) {
    /**
     * modifie les entêtes HTTP
     *
     * @param string $key   le nom de l'entête
     *                      http
     * @param string $value la valeur à assigner
     */
    function set_header($key, $value)
    {
        response()->addHeader($key, $value);
    }
}

if (!function_exists('get_header')) {
    /**
     * modifie les entêtes HTTP
     *
     * @param  string $key le nom de l'entête http
     * @return string|null
     */
    function get_header($key)
    {
        return request()->getHeader($key);
    }
}

if (!function_exists('redirect')) {
    /**
     * modifie les entêtes HTTP
     *
     * @param  string|array $path Le path de rédirection
     * @return \Bow\Http\Redirect
     */
    function redirect($path = null)
    {
        $redirect = new \Bow\Http\Redirect();

        if ($path !== null) {
            $redirect->to($path);
        }

        return $redirect;
    }
}

if (!function_exists('send')) {
    /**
     * alias de echo avec option auto die
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
     * curl lance un requete vers une autre source de resource
     *
     * @param  string $method
     * @param  string $url
     * @param  array  $params
     * @param  bool   $return
     * @param  string $header
     * @return array|null
     */
    function curl($method, $url, array $params = [], $return = false, & $header = null)
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
     * url retourne l'url courant
     *
     * @param string|null $url
     * @param array       $parameters
     *
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
     * pdo retourne l'instance de la connection PDO
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
     * modifie l'instance de la connection PDO
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

if (!function_exists('str')) {
    /**
     * @return \Bow\Support\Str;
     */
    function str()
    {
        return new \Bow\Support\Str();
    }
}

if (!function_exists('collect')) {

    /**
     * retourne une instance de collection
     *
     * @param  array $data [optional]
     * @return \Bow\Support\Collection
     */
    function collect(array $data = [])
    {
        return new Collection($data);
    }
}

if (!function_exists('encrypt')) {
    /**
     * Permet de crypt les données passés en paramètre
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
     * permet de decrypter des données crypté par la function crypt
     *
     * @param  string $data
     * @return string
     */
    function decrypt($data)
    {
        return \Bow\Security\Crypto::decrypt($data);
    }
}

if (!function_exists('start_transaction')) {
    /**
     * Debut un transaction. Désactive l'auto commit
     *
     * @param callable $cb
     */
    function start_transaction(callable $cb = null)
    {
        if ($cb !== null) {
            call_user_func_array($cb, []);
        }
        DB::startTransaction($cb);
    }
}

if (!function_exists('transaction_started')) {
    /**
     * Vérifie l'existance d"une transaction en cours
     *
     * @return bool
     */
    function transaction_started()
    {
        return DB::getPdo()->inTransaction();
    }
}

if (!function_exists('rollback')) {
    /**
     * annuler un rollback
     */
    function rollback()
    {
        DB::rollback();
    }
}

if (!function_exists('commit')) {
    /**
     * valider une transaction
     */
    function commit()
    {
        DB::commit();
    }
}

if (!function_exists('add_event')) {
    /**
     * Alias de la class Event::on
     *
     * @param  string                $event
     * @param  callable|array|string $fn
     * @return Event;
     * @throws \Bow\Exception\EventException
     */
    function add_event($event, $fn)
    {
        if (!is_string($event)) {
            throw new \Bow\Exception\EventException('Le premier paramètre doit être une chaine de caractère.', 1);
        }

        return call_user_func_array([emitter(), 'on'], [$event, $fn]);
    }
}

if (!function_exists('add_event_once')) {
    /**
     * Alias de la class Event::once
     *
     * @param  string                $event
     * @param  callable|array|string $fn
     * @return Event;
     * @throws \Bow\Exception\EventException
     */
    function add_event_once($event, $fn)
    {
        if (!is_string($event)) {
            throw new \Bow\Exception\EventException('Le premier paramètre doit être une chaine de caractère.', 1);
        }
        return call_user_func_array([emitter(), 'once'], [$event, $fn]);
    }
}

if (!function_exists('add_transmisson_event')) {
    /**
     * Alias de la class Event::once
     *
     * @param  string                $event
     * @param  callable|array|string $fn
     * @return Event;
     * @throws \Bow\Exception\EventException
     */
    function add_transmisson_event($event = null, $fn)
    {
        if (!is_string($event)) {
            throw new \Bow\Exception\EventException('Le premier paramètre doit être une chaine de caractère.', 1);
        }
        return call_user_func_array([emitter(), 'onTransmission'], [$event, $fn]);
    }
}

if (!function_exists('emitter')) {
    /**
     * Alias de la class Event::on
     *
     * @return Event;
     * @throws \Bow\Exception\EventException
     */
    function emitter()
    {
        return Event::instance();
    }
}

if (!function_exists('emit_event')) {
    /**
     * Alias de la class Event::emit
     *
     * @param  string $event
     * @throws \Bow\Exception\EventException
     */
    function emit_event($event)
    {
        if (!is_string($event)) {
            throw new \Bow\Exception\EventException('Le premier paramètre doit être une chaine de caractère.', 1);
        }
        call_user_func_array([emitter(), 'emit'], func_get_args());
    }
}

if (!function_exists('flash')) {
    /**
     * Permet ajouter un nouveau flash
     * e.g flash('error', 'An error occured');
     *
     * @param string $key     Le nom du niveau soit ('error', 'info', 'warn', 'danger','success')
     * @param string $message Le message du flash
     *
     * @return mixed
     */
    function flash($key, $message)
    {
        return Session::flash($key, $message);
    }
}

if (!function_exists('email')) {
    /**
     * Alias sur SimpleMail et Smtp
     *
     * @param null|string $view     la view
     * @param array       $data     la view
     * @param \Closure    $callable
     *
     * @return Mail|bool
     */
    function email($view = null, $data = [], \Closure $callable = null)
    {
        if ($view === null) {
            $email = new Mail(config()->getMailConfig());
            $email->configure();
            return $email;
        }

        return Mail::send($view, $data, $callable);
    }
}

if (!function_exists('raw_email')) {
    /**
     * Alias sur SimpleMail et Smtp
     *
     * @param  string array $to
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
     * session
     *
     * @param  mixed $value
     * @return mixed
     */
    function session($value = null)
    {
        if ($value === null) {
            return new Session();
        }

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                Session::add($key, $item);
            }
        }

        return Session::get($value, null);
    }
}

if (!function_exists('cookie')) {
    /**
     * aliase sur la classe Cookie.
     *
     * @param  null       $key
     * @param  null       $data
     * @param  int        $expirate
     * @param  null       $path
     * @param  null       $domain
     * @param  bool|false $secure
     * @param  bool|true  $http
     * @return null|string
     */
    function cookie($key = null, $data = null, $expirate = 3600, $path = null, $domain = null, $secure = false, $http = true)
    {
        if ($key === null) {
            return Cookie::all();
        }

        if ($key !== null && $data == null) {
            return Cookie::get($key);
        }

        if ($key !== null && $data !== null) {
            return Cookie::add($key, $data, $expirate, $path, $domain, $secure, $http);
        }

        return null;
    }
}

if (!function_exists('validator')) {
    /**
     * Elle permet de valider les inforations sur le critère bien définie
     *
     * @param  array $inputs Les données a validé
     * @param  array $rules  Les critaires de validation
     * @return \Bow\Validation\Validate
     */
    function validator(array $inputs, array $rules)
    {
        return \Bow\Validation\Validator::make($inputs, $rules);
    }
}

if (!function_exists('bow_date')) {
    /**
     * @param null $date
     * @return \Bow\Support\DateAccess
     */
    function bow_date($date = null)
    {
        return new \Bow\Support\DateAccess($date);
    }
}

if (!function_exists('public_path')) {
    /**
     * Dossier des publics
     *
     * @return string
     */
    function public_path($path = '')
    {
        return trim(rtrim(config('app.static'), '/').'/'.ltrim($path, '/'), '/');
    }
}

if (!function_exists('storage_path')) {
    /**
     * Dossier des storages
     *
     * @return string
     */
    function storage_path($path = '')
    {
        return trim(rtrim(config('resource.storage'), '/').'/'.ltrim($path, '/'), '/');
    }
}

if (!function_exists('assets')) {
    /**
     * Dossier des assets
     *
     * @return string
     */
    function assets($path = '')
    {
        return trim(rtrim(config('app.assets'), '/').'/'.ltrim($path, '/'), '/');
    }
}

if (!function_exists('str')) {
    /**
     * @return \Bow\Support\Str
     */
    function str()
    {
        return new \Bow\Support\Str();
    }
}

if (!function_exists('route')) {
    /**
     * Route
     *
     * @param  string $name Le nom de la route nommé
     * @param  array  $data Les données à
     *                      assigner
     * @return string
     */
    function route($name, array $data = [])
    {
        $routes = config('app.routes');

        if (!isset($routes[$name])) {
            throw new \InvalidArgumentException($name .' n\'est pas un nom définie.', E_USER_ERROR);
        }

        $url = $routes[$name];

        foreach ($data as $key => $value) {
            $url = str_replace(':'. $key, $value, $url);
        }

        return rtrim(env('APP_URL'), '/').'/'.ltrim($url, '/');
    }
}

if (!function_exists('e')) {
    /**
     * Echape les tags HTML dans la chaine.
     *
     * @param  string $value
     * @return string
     */
    function e($value)
    {
        return htmlentities($value, ENT_QUOTES, 'UTF-8', false);
    }
}

if (!function_exists('form')) {
    /**
     * @return \Bow\Http\Form
     */
    function form()
    {
        return \Bow\Http\Form::singleton();
    }
}

if (!function_exists('ftp')) {
    /**
     * Alias sur le connection FTP.
     *
     * @param  array $c configuration FTP
     * @return \Bow\Resource\Ftp\FTP
     */
    function ftp(array $c = [])
    {
        return Storage::ftp($c);
    }
}

if (!function_exists('s3')) {
    /**
     * Alias sur le connection S3.
     *
     * @param  array $c configuration S3
     * @return \Bow\Resource\AWS\AwsS3Client
     */
    function s3(array $c = [])
    {
        return Storage::s3($c);
    }
}

if (!function_exists('cache')) {
    /**
     * Alias sur le connection FTP.
     *
     * @param  string $key
     * @param  mixed  $value
     * @return mixed
     */
    function cache($key = null, $value = null)
    {
        if ($key !== null && $value === null) {
            return Cache::get($key);
        }

        return Cache::add($key, $value);
    }
}

if (!function_exists('back')) {
    /**
     * @param int   $status
     * @param array $headers
     */
    function back($status = 302, $headers = [])
    {
        redirect()->back($status, $headers);
    }
}

if (!function_exists('bow_hash')) {
    /**
     * Alias sur la class Hash.
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

if (!function_exists('faker')) {
    /**
     * Alias sur la class Filler.
     *
     * @param  string $type
     * @return mixed
     */
    function faker($type = null)
    {
        if (is_null($type)) {
            return new Faker();
        }

        $params = array_slice(func_get_args(), 1);

        if (method_exists(Faker::class, $type)) {
            return call_user_func_array([Faker::class, $type], $params);
        }

        return null;
    }
}

if (!function_exists('trans')) {
    /**
     * @param $key
     * @param $data
     * @param bool $choose
     * @return string
     */
    function trans($key, $data = [], $choose = null)
    {
        app()->bind(
            'trans',
            function ($config) {
                return new \Bow\Translate\Translator($config['app.lang'], $config['app.']);
            }
        );

        return \Bow\Translate\Translator::make($key, $data, $choose);
    }
}

if (!function_exists('__')) {
    /**
     * Alise de trans
     *
     * @param  $key
     * @param  $data
     * @param  bool $choose
     * @return string
     */
    function __($key, $data = [], $choose = null)
    {
        return trans($key, $data, $choose);
    }
}

if (!function_exists('env')) {
    /**
     * @param $key
     * @param $default
     * @return string
     */
    function env($key, $default = null)
    {
        if (Env::isLoaded()) {
            return Env::get($key, $default);
        }

        return $default;
    }
}

if (!function_exists('abort')) {
    /**
     * @param int    $code
     * @param string $message
     * @param array  $headers
     */
    function abort($code = 500, $message = '', array $headers = [])
    {
        response()->statusCode($code);

        foreach ($headers as $key => $value) {
            response()->addHeader($key, $value);
        }

        die($message);
    }
}

if (!function_exists('abort_if')) {
    /**
     * @param boolean $boolean
     * @param int     $code
     */
    function abort_if($boolean, $code)
    {
        if ($boolean) {
            abort($code);
        }
    }
}

if (!function_exists('app_mode')) {
    /**
     * @return string
     */
    function app_mode()
    {
        return env('MODE');
    }
}

if (!function_exists('app_lang')) {
    /**
     * @return string
     */
    function app_lang()
    {
        return request()->lang();
    }
}

if (!function_exists('old')) {
    /**
     * @param string $key
     *
     * @return mixed
     */
    function old($key)
    {
        return request()->old($key);
    }
}

if (!function_exists('format_validation_errors')) {
    /**
     * Formate validation erreur.
     *
     * @param  array $errors
     * @return array
     */
    function format_validation_errors(array $errors)
    {
        $validations = [];

        foreach ($errors as $key => $error) {
            $validations[$key] = $error[0];
        }

        return $validations;
    }
}
