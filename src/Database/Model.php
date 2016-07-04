<?php
namespace Bow\Database;

use Bow\Support\Str;
use Bow\Exception\ModelException;

/**
 * Class Model
 *
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Database
 */
abstract class Model
{
    /**
     * Le nom de la table courrente
     *
     * @var string
     */
    protected static $table = null;

    /**
     * Facade, implementation de la fonction magic __callStatic de PHP
     *
     * @param string $method Le nom de la method a appelé
     * @param array $args    Les arguments a passé à la fonction
     * @throws ModelException
     * @return \Bow\Database\Table
     */
    public static function __callStatic($method, $args)
    {
        $scope = "scope" . ucfirst($method);
        $table = Database::table(Str::lower(static::$table));

        if (method_exists($ins = new static, $scope)) {
            if (method_exists($table, $method)) {
                throw new ModelException("$method ne peut pas être utiliser comme fonction d'aliase.", E_ERROR);
            }

            return call_user_func_array([$ins, $scope], $args);
        }

        if (method_exists($table, $method)) {

            $instance = call_user_func_array([$table, $method], $args);

            if (in_array($method, static::avalableMethod())) {

                if (!is_array($instance)) {
                    $instance = [$instance];
                }

                $custumFieldsLists = ["create_at", "update_at", "expires_at", "login_at", "sign_at"];

                if (method_exists(static::class, "customDate")) {
                    $custumFieldsLists = array_merge($custumFieldsLists, static::customDate());
                }

                foreach($instance as $value) {
                    foreach($value as $key => $content) {
                        if (in_array($key, $custumFieldsLists)) {
                            $value->$key = new \Carbon\Carbon($content);
                        }
                    }
                }
            }

            return $instance;
        }

        throw new ModelException("methode $method n'est définie.", E_ERROR);
    }

    /**
     * @return array
     */
    private static function avalableMethod()
    {
        return ["get", "getOne", "find"];
    }
}