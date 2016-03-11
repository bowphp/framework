<?php

/*------------------------------------------------
|
|	HELPER
|	======
|	Définir des liens symbolique de l'ensemble des
|	fonctions de Bow.
|
*/

use Bow\Support\Util;
use Bow\Support\Event;
use Bow\Support\Session;
use Bow\Support\Security;
use Bow\Support\Resource;
use Bow\Database\Database;
use Bow\Core\AppConfiguration;

define("SELECT", Database::SELECT);
define("INSERT", Database::INSERT);
define("UPDATE", Database::UPDATE);
define("DELETE", Database::DELETE);

global $request;
global $response;

$app_dir = dirname(dirname(dirname(__DIR__)));

$response = \Bow\Http\Response::configure(AppConfiguration::configure(require $app_dir . "/config/application.php"));
$request  = \Bow\Http\Request::configure();

Database::configure(require $app_dir . "/config/database.php");
Resource::configure(require $app_dir . "/config/resource.php");

if (!function_exists("configuration")) {
    /**
     * Application configuration
     * @return AppConfiguration
     */
    function configuration() {
        return AppConfiguration::takeInstance();
    }
}

if (!function_exists("db")) {
    /**
     * @param string $database
     * @param callable $cb
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
     * view
     * @param string $template
     * @param array $data
     * @param int $code
     * @return mixed
     */
    function view($template, $data = [], $code = 200) {
        return $GLOBALS["response"]->view($template, $data, $code);
    }
}

if (!function_exists("table")) {
    /**
     * @param string $tableName, le nom d'un table.
     * @return Bow\Database\Table
     */
    function table($tableName) {
        return Database::table($tableName);
    }
}

if (!function_exists("query_maker")) {
    /**
     * @param $sql
     * @param $data
     * @param $cb
     * @param $method
     * @return null
     */
    function query_maker($sql, $data, $cb, $method) {
        $rs = null;

        if (method_exists(Database::class, $method)) {
            $rs = Database::$method($sql, $data);
        }

        if (is_callable($cb)) {
            call_user_func_array($cb, [$rs]);
        }

        return $rs;
    }
}

if (!function_exists("last_insert_id")) {
    /**
     * Retourne le dernier ID suite a une requete INSERT sur un table dont ID est
     * auto_increment.
     * @return int|null
     */
    function last_insert_id() {
        return Database::lastInsertId();
    }
}

if (!function_exists("query_response")) {
    /**
     * @param string $method
     * @param array $param
     * @return null|mixed
     */
    function query_response($method, $param) {

        if (method_exists($GLOBALS["response"], $method)) {
            return call_user_func_array([$GLOBALS["response"], $method], array_slice(func_get_args(), 1));
        }

        return null;
    }
}

if (!function_exists("get_last_db_error")) {
    /**
     * Retourne les informations de la dernière requete
     * @return array
     */
    function get_last_db_error() {
        return Database::getLastErreur();
    }
}

if (!function_exists("select")) {
    /**
     * statement lance des requete SQL de type SELECT
     * @param string $sql
     * @param array $data
     * @param callable $cb
     * @return integer
     */
    function select($sql, array $data = [], $cb = null) {
        return query_maker($sql, $data, $cb, "select");
    }
}

if (!function_exists("insert")) {
    /**
     * statement lance des requete SQL de type INSERT
     * @param string $sql
     * @param array $data
     * @param callable $cb
     * @return integer
     */
    function insert($sql, array $data = [], $cb = null) {
        return query_maker($sql, $data, $cb, "insert");
    }
}

if (!function_exists("delete")) {
    /**
     * statement lance des requete SQL de type DELETE
     * @param string $sql
     * @param array $data
     * @param callable $cb
     * @return integer
     */
    function delete($sql, array $data = [], $cb = null) {
        return query_maker($sql, $data, $cb, "delete");
    }
}

if (!function_exists("update")) {
    /**
     * update lance des requete SQL de type UPDATE
     * @param string $sql
     * @param array $data
     * @param callable $cb
     * @return integer
     */
    function update($sql, array $data = [], $cb = null) {
        return query_maker($sql, $data, $cb, "update");
    }
}

if (!function_exists("statement")) {
    /**
     * statement lance des requete SQL de type CREATE TABLE|ALTER TABLE|RENAME|DROP TABLE
     * @param string $sql
     * @param array $data
     * @return integer
     */
    function statement($sql, array $data = []) {
        return query_maker($sql, $data, null, "statement");
    }
}

if (!function_exists("kill")) {
    /**
     * kill c'est une alias de die, sauf le fait qu'il peut logger
     * le message que vous lui donnée.
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
     */
    function body() {
        return $GLOBALS["request"]->body();
    }
}

if (!function_exists("files")) {
    /**
     * files, fonction de type collection
     * manipule la variable global $_FILES
     */
    function files() {
        return $GLOBALS["request"]->files();
    }
}

if (!function_exists("query")) {
    /**
     * query, fonction de type collection
     * manipule la variable global $_GET
     */
    function query() {
        return $GLOBALS["request"]->query();
    }
}

if (!function_exists("request")) {
    /**
     * @return Bow\Http\Request
     */
    function request() {
        return $GLOBALS["request"];
    }
}

if (!function_exists("dump")) {
    /**
     * dump, fonction de debug de variable
     * Elle vous permet d'avoir un coloration
     * synthaxique des types de donnée.
     */
    function dump() {
        call_user_func_array([Util::class, "dump"], func_get_args());
    }
}

if (!function_exists("csrf")) {
    /**
     * csrf, fonction permetant de crée automatiquement un gestionnaire de csrf
     * @param int $time
     * @return \StdClass
     */
    function csrf($time = null) {
        return Security::createTokenCsrf($time);
    }
}

if (!function_exists("csrf_token")) {
    /**
     * csrf, fonction permetant de générer un token
     * @return string
     */
    function csrf_token() {
        return Security::generateTokenCsrf();
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
            Resource::setUploadFileName($filename);
        }

        if (!is_null($dirname)) {
            Resource::setUploadDirectory($dirname);
        }

        return (object) Resource::store($file);
    }
}

if (!function_exists("json")) {
    /**
     * json, permet de lance des reponses server de type json
     * @param array $data
     * @param integer $code=200
     * @return mixed
     */
    function json($data, $code = 200) {
        return query_response("json", $data, $code);
    }
}

if (!function_exists("set_code")) {
    /**
     * statuscode, permet de changer le code de la reponse du server
     * @param int $code=200
     * @return mixed
     */
    function set_code($code) {
        return $GLOBALS["response"]->setCode($code);
    }
}

if (!function_exists("sanitaze")) {
    /**
     * sanitaze, épure un variable d'information indésiration
     * eg. sanitaze("j\'ai") => j'ai
     * @param mixed $data
     * @return mixed
     */
    function sanitaze($data) {
        if (is_int($data) || is_string($data)) {
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
     * @param mixed $data
     * @return mixed
     */
    function secure($data) {
        if (is_int($data)) {
            return $data;
        } else {
            return Security::sanitaze($data, true);
        }
    }
}

if (!function_exists("response")) {
    /**
     * response, manipule une instance de Response::class
     * @param string $template, le message a envoyer
     * @param integer $code, le code d'erreur
     * @param string $type, le type mime du contenu
     * @return Bow\Http\Response
     */
    function response($template = null, $code = 200, $type = "text/html") {

        if (is_null($template)) {
            return $GLOBALS["response"];
        }

        set_header("Content-Type", $type);
        set_code($code);
        query_response("send", $template);

        return $GLOBALS["response"];
    }
}

if (!function_exists("set_header")) {
    /**
     * modifie les entêtes HTTP
     * @param string $key le nom de l'entête http
     * @param string $value la valeur à assigner
     */
    function set_header($key, $value) {
        query_response("setHeader", $key, $value);
    }
}

if (!function_exists("redirect")) {
    /**
     * modifie les entêtes HTTP
     * @param string $path le path de rédirection
     */
    function redirect($path) {
        query_response("redirect", $path);
    }
}

if (!function_exists("send_file")) {
    /**
     * sendfile c'est un alias de require, mais vas chargé les fichiers contenu dans
     * la vie de l'application. Ici <code>sendfile</code> résoue le problème de scope.
     * @param string $filename le nom du fichier
     * @param array $bind les données la exporter
     */
    function send_file($filename, $bind = []) {
        query_response("sendFile", $filename, $bind);
    }
}

if (!function_exists("send")) {
    /**
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
     * @return string $url
     */
    function url() {
        return $GLOBALS["request"]->url();
    }
}


if (!function_exists("pdo")) {
    /**
     * pdo retourne l'instance de la connection PDO
     * @return PDO
     */
    function pdo() {
        return Database::pdo();
    }
}

if (!function_exists("execute_function")) {
    /**
     * lance une fonction de controller ou un multitude de callback
     * @param $cb
     * @param $req
     * @param array $names
     * @return mixed
     */
    function execute_function($cb, $req, $names = []) {
        return Util::launchCallback($cb, $req, $names);
    }
}


if (!function_exists("collect")) {

    /**
     * retourne une instance de collection
     * @return \Bow\Support\Collection
     */
    function collect() {
        if (func_num_args() == 0) {
            $col = new \Bow\Support\Collection();
        } else {
            $col = new \Bow\Support\Collection(func_get_args());
        }

        return $col;
    }
}

if (!function_exists("crypt")) {
    /**
     * @param string $data
     * @return string
     */
    function crypt($data) {
        return Security::encrypt($data);
    }
}

if (!function_exists("decrypt")) {
    /**
     * @param string $data
     * @return string
     */
    function decrypt($data) {
        return Security::decrypt($data);
    }
}

if (!function_exists("transaction")) {
    /**
     * debut un transaction. Désactive l'auto commit
     * @param $cb
     */
    function transaction($cb) {
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
     * @param string $event_name
     * @throws \Bow\Exception\EventException
     * @param callable|array $fn
     */
    function event($event_name, $fn) {
        if (!is_string($event_name)) {
            throw new \Bow\Exception\EventException("Le premier parametre doit etre une chaine de caractere", 1);
        }

        call_user_func_array([Event::class, "on"], [$event_name, $fn]);
    }
}

if (!function_exists("trigger")) {
    /**
     * @param string $event_name
     * @throws \Bow\Exception\EventException
     */
    function trigger($event_name) {
        if (!is_string($event_name)) {
            throw new \Bow\Exception\EventException("Le premier parametre doit etre une chaine de caractere", 1);
        }
        
        call_user_func_array([Event::class, "trigger"], func_get_args());
    }
}

if (!function_exists("flash")) {
    /**
     * flash
     * @return \Bow\Support\Flash
     */
    function flash() {
        return Session::get("bow.flash");
    }
}