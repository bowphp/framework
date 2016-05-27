<?php
namespace Bow\Database;

use Bow\Support\Str;
use Bow\Exception\ModelException;
use Bow\Exception\TableException;

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
     * implementation de la fonction magic __call
     *
     * @param string $method
     * @param array  $args
     *
     * @return mixed
     * @throws ModelException
     */
    public function __call($method, $args)
    {
        $scope = "scope" . ucfirst($method);

        if (method_exists($this, $scope)) {
            return call_user_func_array($scope, $args);
        }

        throw new ModelException("$method n'existe pas", E_ERROR);
    }


    /**
     * Facade, implementation de la fonction magic __callStatic de PHP
     *
     * @param string $method
     * @param array $arg
     * @throws TableException
     * @return \Bow\Database\Table
     */
    public static function __callStatic($method, $arg)
    {
        $table = static::$table;
        $table = Database::table(Str::lower($table));

        if (method_exists($table, $method)) {
            return call_user_func_array([$table, $method], $arg);
        } else {
            throw new TableException("methode $method n'est définie.", E_ERROR);
        }
    }
}