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
        $scope = "custom" . ucfirst($method);
        $table = static::$table;
        $table = Database::table(Str::lower(static::$table));
        if (method_exists($ins = new static, $scope)) {
            if (method_exists($table, $method)) {
                throw new ModelException("$method ne peut pas être utiliser comme fonction d'aliase.", E_ERROR);
            }

            return call_user_func_array([$ins, $scope], $args);
        } else {
            if (method_exists($table, $method)) {
                return call_user_func_array([$table, $method], $args);
            }
        }

        throw new ModelException("methode $method n'est définie.", E_ERROR);
    }
}