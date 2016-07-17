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

        /**
         * Lancement de l'execution des fonctions aliase définir dans les classe
         * héritant de la classe Model.
         *
         * Les classes  definir définir avec la montion scope.
         */
        if (method_exists($instance = new static, $scope)) {
            if (method_exists($table, $method)) {
                throw new ModelException("$method ne peut pas être utiliser comme fonction d'aliase.", E_ERROR);
            }
            return call_user_func_array([$instance, $scope], $args);
        }

        /**
         * Lancement de l'execution des fonctions liée a l'instance de la classe Table
         */
        if (method_exists($table, $method)) {
            $instance = call_user_func_array([$table, $method], $args);
            return static::carbornize($instance, $method);
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

    /**
     * @param mixed $instance
     * @param string $method
     * @return array
     */
    private static function carbornize($instance, $method)
    {
        if (in_array($method, static::avalableMethod())) {

            if (!is_array($instance)) {
                $instance = [$instance];
            }

            $custumFieldsLists = ["create_at", "update_at", "expires_at", "login_at", "sigin_at"];

            if (method_exists($instance, "customDate")) {
                $custumFieldsLists = array_merge($custumFieldsLists, $instance::customDate());
            }

            foreach($instance as $value) {
                if (!is_array($value)) {
                    $value = [$value];
                }
                foreach($value as $key => $content) {
                    if (in_array($key, $custumFieldsLists)) {
                        $value->$key = new \Carbon\Carbon($content);
                    }
                }
            }
        }

        return $instance;
    }
}
