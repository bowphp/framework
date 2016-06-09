<?php
/*------------------------------------------------
|
|	BOW HELPER
|	==========
|	Définir des liens symbolique de l'ensemble des
|	fonctions de Bow.
|
*/

use Bow\Http\Request;
use Bow\Support\Util;
use Bow\Http\Response;
use Bow\Http\RequestData;
use Bow\Support\Security;
use Bow\Database\Database;
use Bow\Support\Collection;
use Bow\Support\Session\Event;
use Bow\Core\AppConfiguration;
use Bow\Support\Session\Session;
use Bow\Support\Resource\Storage;

define("SELECT", Database::SELECT);
define("INSERT", Database::INSERT);
define("UPDATE", Database::UPDATE);
define("DELETE", Database::DELETE);

if (!function_exists("configuration")) {
    /**
     * Application configuration
     *
     * @return AppConfiguration
     */
    function configuration() {
        $app_dir = dirname(dirname(dirname(dirname(dirname(__DIR__)))));
        return AppConfiguration::configure(require $app_dir . "/config/bootstrap.php");
    }
}

// Configuration de la Request et de la Response
Response::configure(configuration());

// Configuration de la base de donnée
Database::configure(configuration()->getDatabaseConfiguration());

// Configuration de la resource de l'application.
Storage::configure(configuration()->getResourceConfiguration());


if (!function_exists("response")) {
    /**
     * response, manipule une instance de Response::class
     *
     * @param string $template, le message a envoyer
     * @param int $code, le code d'erreur
     * @param string $type, le type mime du contenu
     * @return Response
     */
    function response($template = null, $code = 200, $type = "text/html") {

        if (is_null($template)) {
            return Response::takeInstance();
        }

        set_header("Content-Type", $type);
        set_response_code($code);
        query_response("send", $template);

        return Response::takeInstance();
    }
}

if (!function_exists("request")) {
    /**
     * répresente le classe Request
     *
     * @return Request
     */
    function request() {
        return Request::configure();
    }
}

if (!function_exists("db")) {
    /**
     * permet de se connecter sur une autre base de donnée
     * et retourne l'instance de la Database
     *
     * @param string $database le nom de la configuration de la db
     * @param callable $cb la fonction de rappel
     *
     * @return Database, the Database reference
     */
    function db($database = null, $cb = null) {

        if (is_string($database)) {
            switch_to($database, $cb);
        } else {
            switch_to("default", $cb);
        }

        return Database::takeInstance();
    }
}

if (!function_exists("view")) {
    /**
     * view aliase sur Response::view
     *
     * @param string $template
     * @param array|int $data
     * @param int $code
     *
     * @return Response
     */
    function view($template, $data = [], $code = 200) {
        if (is_int($data)) {
            $code = $data;
            $data = [];
        }
        return response()->view($template, $data, $code);
    }
}

if (!function_exists("table")) {
    /**
     * table aliase Database::table
     *
     * @param string $tableName, le nom d'un table.
     * @param string $zoneName, le nom de la zone sur laquelle la requete sera faite.
     * @return Bow\Database\Table
     */
    function table($tableName, $zoneName = null) {
        if (is_string($zoneName)) {
            db($zoneName);
        }
        return Database::table($tableName);
    }
}

if (!function_exists("query_maker")) {
    /**
     * fonction d'astuce
     *
     * @param $sql
     * @param $data
     * @param $cb
     * @param $method
     *
     * @return mixed
     */
    function query_maker($sql, $data, $cb, $method) {
        $rs = null;
        $err = null;
        if (is_callable($data)) {
            $cb = $data;
            $data = [];
        }

        if (method_exists(Database::class, $method)) {
            $rs = Database::$method($sql, $data);
            $err = Database::getLastError();
        }

        if (is_callable($cb)) {
            return call_user_func_array($cb, [$err, $rs]);
        }

        return $rs;
    }
}

if (!function_exists("last_insert_id")) {
    /**
     * Retourne le dernier ID suite a une requete INSERT sur un table dont ID est
     * auto_increment.
     *
     * @param string $name
     * @return int
     */
    function last_insert_id($name = null) {
        return Database::lastInsertId($name);
    }
}

if (!function_exists("query_response")) {
    /**
     * @param string $method
     * @param array $param
     *
     * @return mixed
     */
    function query_response($method, $param) {

        if (method_exists(response(), $method)) {
            return call_user_func_array([response(), $method], array_slice(func_get_args(), 1));
        }

        return null;
    }
}

if (!function_exists("db_error")) {
    /**
     * Retourne les informations de la dernière requete
     *
     * @return \Bow\Database\DatabaseErrorHandler
     */
    function db_error() {
        return Database::getLastError();
    }
}

if (!function_exists("select")) {
    /**
     * statement lance des requete SQL de type SELECT
     *
     * select("SELECT * FROM users", function (Bow\Database\DatabaseErrorHandler $err, array $data) {
     *    if ($err->hasError()) {
     *          $err->throwError();
     *    }
     *    json($data);
     * });
     *
     * @param string $sql
     * @param array $data
     * @param callable $cb
     *
     * @return int|array|StdClass
     */
    function select($sql, array $data = [], $cb = null) {
        return query_maker($sql, $data, $cb, "select");
    }
}

if (!function_exists("select_one")) {
    /**
     * statement lance des requete SQL de type SELECT
     *
     * @param string $sql
     * @param array $data
     * @param callable $cb
     *
     * @return int|array|StdClass
     */
    function select_one($sql, array $data = [], $cb = null) {
        return query_maker($sql, $data, $cb, "selectOne");
    }
}

if (!function_exists("insert")) {
    /**
     * statement lance des requete SQL de type INSERT
     *
     * @param string $sql
     * @param array $data
     * @param callable $cb
     *
     * @return int
     */
    function insert($sql, array $data = [], $cb = null) {
        return query_maker($sql, $data, $cb, "insert");
    }
}

if (!function_exists("delete")) {
    /**
     * statement lance des requete SQL de type DELETE
     *
     * @param string $sql
     * @param array $data
     * @param callable $cb
     *
     * @return int
     */
    function delete($sql, array $data = [], $cb = null) {
        return query_maker($sql, $data, $cb, "delete");
    }
}

if (!function_exists("update")) {
    /**
     * update lance des requete SQL de type UPDATE
     *
     * @param string $sql
     * @param array $data
     * @param callable $cb
     *
     * @return int
     */
    function update($sql, array $data = [], $cb = null) {
        return query_maker($sql, $data, $cb, "update");
    }
}

if (!function_exists("statement")) {
    /**
     * statement lance des requete SQL de type CREATE TABLE|ALTER TABLE|RENAME|DROP TABLE
     *
     * @param string $sql
     * @param array $data
     *
     * @return int
     */
    function statement($sql, array $data = []) {
        return query_maker($sql, $data, null, "statement");
    }
}

if (!function_exists("kill")) {
    /**
     * kill c'est une alias de die, sauf le fait qu'il peut logger
     * le message que vous lui donnée.
     *
     * @param string $message
     * @param boolean $log=false
     */
    function kill($message = null, $log = false) {
        if ($log === true) {
            log($message);
        }
        die($message);
    }
}

if (!function_exists("slugify")) {
    /**
     * slugify, transforme un chaine de caractère en slug
     * eg. la chaine "58 comprendre bow framework" -> "58-comprendre-bow-framework"
     *
     * @param string $str
     * @return string
     */
    function slugify($str) {
        return \Bow\Support\Str::slugify($str);
    }
}

if (!function_exists("body")) {
    /**
     * body, fonction de type collection
     * manipule la variable global $_POST
     *
     * @return RequestData
     */
    function body() {
        return request()->body();
    }
}

if (!function_exists("files")) {
    /**
     * files, fonction de type collection
     * manipule la variable global $_FILES
     *
     * @return RequestData
     */
    function files() {
        return request()->files();
    }
}

if (!function_exists("query")) {
    /**
     * query, fonction de type collection
     * manipule la variable global $_GET
     *
     * @return RequestData
     */
    function query() {
        return request()->query();
    }
}

if (!function_exists("input")) {
    /**
     * input, fonction de type collection
     * manipule la variable global $_GET, $_POST, $_FILES
     *
     * @return RequestData
     */
    function input() {
        return request()->allInput();
    }
}

if (!function_exists("dump")) {
    /**
     * dump, fonction de debug de variable
     * elle vous permet d'avoir un coloration
     * synthaxique des types de donnée.
     */
    function dump() {
        call_user_func_array([Util::class, "dump"], secure(func_get_args()));
    }
}

if (!function_exists("create_csrf")) {
    /**
     * create_csrf, fonction permetant de récupérer le token généré
     *
     * @param int $time [optional]
     * @return \StdClass
     */
    function create_csrf($time = null) {
        Security::createCsrfToken($time);
        return Security::getCsrfToken();
    }
}


if (!function_exists("csrf_token")) {
    /**
     * create_csrf, fonction permetant de récupérer le token généré
     *
     * @return string
     */
    function csrf_token() {
        return create_csrf()->token;
    }
}

if (!function_exists("generate_token_csrf")) {
    /**
     * csrf, fonction permetant de générer un token
     *
     * @return string
     */
    function gen_csrf_token() {
        return Security::generateCsrfToken();
    }
}

if (!function_exists("verify_csrf")) {
    /**
     * verify_token_csrf, fonction permetant de vérifier un token
     *
     * @param string $token l'information sur le token
     * @param bool $strict vérifie le token et la date de création avec à la valeur time()
     * @return string
     */
    function verify_csrf($token, $strict = false) {
        return Security::verifyCsrfToken($token, $strict);
    }
}

if (!function_exists("csrf_time_is_expirate")) {
    /**
     * csrf, fonction permetant de générer un token
     *
     * @param string $time
     * @return string
     */
    function csrf_time_is_expirate($time = null) {
        return Security::tokenCsrfTimeIsExpirate($time);
    }
}

if (!function_exists("store")) {
    /**
     * store, effecture l'upload d'un fichier vers un repertoire
     * @param array $file, le fichier a uploadé.
     * @param string|null $filename nom du fichier
     * @param string|null $dirname nom du dossier de destination.
     * @return StdClass
     */
    function store(array $file, $filename = null, $dirname = null) {
        if (!is_null($filename) && is_string($filename)) {
            Storage::setUploadFileName($filename);
        }

        if (!is_null($dirname)) {
            Storage::setUploadDirectory($dirname);
        }

        return (object) Storage::store($file);
    }
}

if (!function_exists("json")) {
    /**
     * json, permet de lance des reponses server de type json
     *
     * @param array $data
     * @param int $code=200
     * @return mixed
     */
    function json($data, $code = 200) {
        return query_response("json", $data, $code);
    }
}

if (!function_exists("set_response_code")) {
    /**
     * statuscode, permet de changer le code de la reponse du server
     *
     * @param int $code=200
     * @return mixed
     */
    function set_response_code($code) {
        return response()->code($code);
    }
}

if (!function_exists("sanitaze")) {
    /**
     * sanitaze, épure un variable d'information indésiration
     * eg. sanitaze("j\'ai") => j'ai
     *
     * @param mixed $data
     * @return mixed
     */
    function sanitaze($data) {
        if (is_numeric($data)) {
            return $data;
        } else {
            return Security::sanitaze($data);
        }
    }
}

if (!function_exists("secure")) {
    /**
     * secure, échape les anti-slashes, les balises html
     * eg. secure("j'ai") => j\'ai
     *
     * @param mixed $data
     * @return mixed
     */
    function secure($data) {
        if (is_numeric($data)) {
            return $data;
        } else {
            return Security::sanitaze($data, true);
        }
    }
}

if (!function_exists("set_header")) {
    /**
     * modifie les entêtes HTTP
     *
     * @param string $key le nom de l'entête http
     * @param string $value la valeur à assigner
     */
    function set_header($key, $value) {
        query_response("set", $key, $value);
    }
}

if (!function_exists("redirect")) {
    /**
     * modifie les entêtes HTTP
     *
     * @param string|array $path Le path de rédirection
     */
    function redirect($path) {
        query_response("redirect", $path);
    }
}

if (!function_exists("send_file")) {
    /**
     * send_file c'est un alias de require, mais vas chargé les fichiers contenu dans
     * la vie de l'application. Ici <code>sendfile</code> résoue le problème de scope.
     *
     * @param string $filename le nom du fichier
     * @param array $bind les données la exporter
     */
    function send_file($filename, $bind = []) {
        query_response("sendFile", $filename, $bind);
    }
}

if (!function_exists("send")) {
    /**
     * alias de echo avec option auto die
     *
     * @param string $data
     */
    function send($data) {
        query_response("send", $data);
    }
}

if (!function_exists("execute_sql")) {
    /**
     * Execute des requêtes sql customisé
     *
     * @param array $option
     * @return array|StdClass|null
     */
    function execute_sql(array $option) {
        return Database::query($option);
    }
}

if (!function_exists("switch_to")) {
    /**
     * switch to, permet de changer de base de donnée.
     *
     * @param string $name nom de l'entré
     * @param callable $cb fonction de callback
     */
    function switch_to($name, $cb = null) {
        Database::switchTo($name, $cb);
    }
}

if (!function_exists("curl")) {
    /**
     * curl lance un requete vers une autre source de resource
     *
     * @param string $url
     * @return array|null
     */
    function curl($url) {
        $ch = curl_init($url);

        if (! curl_setopt($ch, CURLOPT_RETURNTRANSFER, true)) {
            curl_close($ch);
            return null;
        }

        $data = curl_exec($ch);
        curl_close($ch);
        return json_encode($data);
    }
}

if (!function_exists("url")) {
    /**
     * url retourne l'url courant
     *
     * @param string $url
     * @param array $parameters
     *
     * @return string
     */
    function url($url, array $parameters = []) {
        $url = request()->url() . ltrim($url, "/");
        if (count($parameters) > 0) {
            $url .= "?" . http_build_query($parameters);
        }

        return $url;
    }
}


if (!function_exists("pdo")) {
    /**
     * pdo retourne l'instance de la connection PDO
     * @return PDO
     */
    function pdo() {
        return Database::getPdo();
    }
}

if (!function_exists("set_pdo")) {
    /**
     * modifie l'instance de la connection PDO
     *
     * @param PDO $pdo
     * @return PDO
     */
    function set_pdo(PDO $pdo) {
        Database::setPdo($pdo);
        return pdo();
    }
}

if (!function_exists("execute_function")) {
    /**
     * lance une fonction de controller ou un multitude de callback
     * @param callable|string $cb
     * @param mixed $params
     * @param array $names
     * @return mixed
     */
    function execute_function($cb, $params, $names = []) {
        return util()->launchCallback($cb, $params, $names);
    }
}


if (!function_exists("collect")) {

    /**
     * retourne une instance de collection
     *
     * @return \Bow\Support\Collection
     */
    function collect() {
        $col = new Collection();
        $data = func_get_args();

        foreach($data as $key => $param) {
            $col->add($key, $param);
        }

        return $col;
    }
}

if (!function_exists("crypt")) {
    /**
     * Permet de crypt les données passés en paramètre
     *
     * @param string $data
     * @return string
     */
    function crypt($data) {
        return Security::encrypt($data);
    }
}

if (!function_exists("decrypt")) {
    /**
     * permet de decrypter des données crypté par la function crypt
     *
     * @param string $data
     * @return string
     */
    function decrypt($data) {
        return Security::decrypt($data);
    }
}

if (!function_exists("beginTransaction")) {
    /**
     * Debut un transaction. Désactive l'auto commit
     *
     * @param $cb
     */
    function beginTransaction($cb) {
        if ($cb !== null) {
            call_user_func_array($cb, []);
        }

        Database::transaction();
    }
}

if (!function_exists("rollback")) {
    /**
     * annuler un rollback
     */
    function rollback() {
        Database::rollback();
    }
}

if (!function_exists("commit")) {
    /**
     * valider une transaction
     */
    function commit() {
        Database::commit();
    }
}

if (!function_exists("event")) {
    /**
     * Alias de la class Event::on
     *
     * @param string $event_name
     * @throws \Bow\Exception\EventException
     * @param callable|array $fn
     */
    function event($event_name, $fn) {
        if (!is_string($event_name)) {
            throw new \Bow\Exception\EventException("Le premier parametre doit etre une chaine de caractere", 1);
        }

        call_user_func_array([Event::class, "on"], [$event_name, $fn, configuration()->getNamespace()]);
    }
}

if (!function_exists("emit")) {
    /**
     * Alias de la class Event::emit
     *
     * @param string $event_name
     * @throws \Bow\Exception\EventException
     */
    function emit($event_name) {
        if (!is_string($event_name)) {
            throw new \Bow\Exception\EventException("Le premier parametre doit etre une chaine de caractere", 1);
        }
        
        call_user_func_array([Event::class, "emit"], func_get_args());
    }
}

if (!function_exists("flash")) {
    /**
     * flash
     *
     * e.g flash("error", "An error occured");
     *
     * @param string $key Le nom du niveau soit ("error", "info", "warn", "danger","success")
     * @param string $message Le message du flash, Dans le case ou le message n'est pas
     *                        spécifié la fonction rétourne le message du flash concerné
     *                        par la clé
     *
     * @return \Bow\Support\Flash
     */
    function flash($key, $message = null) {
        return Session::flash($key, $message);
    }
}


if (!function_exists("middleware")) {
    /**
     * middleware, Permet de lancer un middleware n'import ou dans votre projet
     *
     * @param string $name Le nom du middleware a lancé
     * @return mixed
     */
    function middleware($name) {
        util()->launchCallback($name, request(), configuration()->getNamespace());
    }
}

if (!function_exists("util")) {
    /**
     * Alais sur la class Util
     *
     * @return Util
     */
    function util() {
        return Util::class;
    }
}

if (!function_exists("bow_mail")) {
    /**
     * @param string|null $type
     *
     * @return \Bow\Mail\SimpleMail|\Bow\Mail\Smtp
     * @throws \Bow\Exception\MailException
     */
    function bow_mail($type = null) {
        $config = configuration()->getMailConfiguration();

        if ($type !== null) {
            if (!in_array($type, ["mail", "smtp"])) {
                $config->driver = $type;
            }
        }

        return Bow\Mail\Mail::confirgure($config);
    }
}

if (! function_exists("env")) {
    /**
     * env manipule les variables d'environement du server.
     *
     * @param $key
     * @param null $value
     *
     * @return bool|string
     */
    function env($key, $value = null) {
        if ($value !== null) {
            return getenv(\Bow\Support\Str::upper($key));
        } else {
            return putenv(\Bow\Support\Str::upper($key) . "=" . $value);
        }

    }
}

if (!function_exists("session")) {
    /**
     * session
     *
     * @param string $key
     * @param mixed $message
     * @return mixed
     */
    function session($key = null, $message = null) {
        if ($key === null && $message === null) {
            return Session::toArray();
        }

        if (Session::has($key)) {
            return Session::get($key);
        }

        if ($key !== null && $message !== null) {
            return Session::add($key, $message);
        }
    }
}

if (!function_exists("cookie")) {
    /**
     * aliase sur la classe Cookie.
     *
     * @return \Bow\Support\Session\Cookie
     */
    function cookie() {
        return \Bow\Support\Session\Cookie::class;
    }
}

if (!function_exists("validate")) {
    /**
     * Elle permet de valider les inforations sur le critère bien définie
     *
     * @param array $inputs Les données a validé
     * @param array $rules  Les critaires de validation
     * @return \Bow\Support\Validate\Validate
     */
    function validate(array $inputs, array $rules) {
        return \Bow\Support\Validate\Validator::make($inputs, $rules);
    }
}

if (!function_exists("bow_date")){
    /**
     * @param null $date
     * @return \Bow\Support\DateAccess
     */
    function bow_date($date = null) {
        return new \Bow\Support\DateAccess($date);
    }
}

if (!function_exists("public_path")) {
    /**
     * @return string
     */
    function public_path() {
        return configuration()->getPublicPath();
    }
}

if (!function_exists("str")) {
    /**
     * @return \Bow\Support\Str
     */
    function str() {
        return \Bow\Support\Str::class;
    }
}

if (!function_exists("route")) {
    /**
     * Route
     *
     * @param string $name Le nom de la route nommé
     * @param array $data Les données à assigner
     * @return string
     */
    function route($name, array $data = []) {
        $routes = configuration()->getApplicationRoutes();

        if (!isset($routes[$name])) {
            throw new \InvalidArgumentException("$name n'est pas un nom définie.", E_USER_ERROR);
        }

        $url = $routes[$name];

        foreach($data as $key => $value) {
            $url = str_replace(":$key", $value, $url);
        }

        return $url;
    }
}