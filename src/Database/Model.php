<?php
namespace Bow\Database;

use Bow\Exception\TableException;
use Bow\Support\Collection;
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
     * @var string
     */
    protected static $primaryKey = 'id';

    /**
     * @var array
     */
    protected $data = [];

    /**
     * Le nom de la table courrente
     *
     * @var string
     */
    protected static $table = null;

    /**
     * find
     *
     * @param mixed $id
     * @param array $select
     * @return Collection|SqlUnity
     * @throws TableException
     */
    public function find($id, $select = ['*'])
    {
        $table = Database::table(static::$table);
        $one = false;
        if (! is_array($id)) {
            $one = true;
            $id = [$id];
        }
        $table->whereIn(static::$primaryKey, $id);
        $table->select($select);

        return $one ? $table->getOne() : $table->get();
    }

    /**
     * @return array
     */
    private static function avalableMethods()
    {
        return ['get', 'getOne', 'find'];
    }

    /**
     * @return array
     */
    private static function avalableFields()
    {
        return ['created_at', 'updated_at', 'expired_at', 'loged_at', 'sigin_at'];
    }

    /**
     * @param mixed $data
     * @param string $method
     * @param mixed $child
     * @return array
     */
    private static function carbornize($data, $method, $child)
    {
        if (!in_array($method, static::avalableMethods())) {
            return $data;
        }

        if (!is_array($data)) {
            $data = [$data];
        }

        $custumFieldsLists = static::avalableFields();

        if (method_exists($child, 'customDate')) {
            $custumFieldsLists = array_merge($custumFieldsLists, $child->customDate());
        }

        foreach($data as $value) {
            if (!is_object($value)) {
                continue;
            }
            foreach($value as $key => $content) {
                if (in_array($key, $custumFieldsLists)) {
                    $value->$key = new \Carbon\Carbon($content);
                }
            }
        }

        if (count($data) == 1) {
            if ($method == 'getOne' || preg_match('/^find/', $method)) {
                $data = end($data);
            }
        }

        return $data;
    }

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
        $scope = 'scope' . ucfirst($method);
        $table = Database::table(Str::lower(static::$table));

        /**
         * Lancement de l'execution des fonctions aliase définir dans les classe
         * héritant de la classe Model.
         *
         * Les classes  definir définir avec la montion scope.
         */
        if (method_exists($child = new static, $scope)) {
            if (method_exists($table, $method)) {
                throw new ModelException($method . ' ne peut pas être utiliser comme fonction d\'aliase.', E_ERROR);
            }
            return call_user_func_array([$child, $scope], $args);
        }

        /**
         * Lancement de l'execution des fonctions liée a l'instance de la classe Table
         */
        if (method_exists($table, $method)) {
            $table = call_user_func_array([$table, $method], $args);
            return static::carbornize($table, $method, $child);
        }

        throw new ModelException('methode ' . $method . ' n\'est définie.', E_ERROR);
    }
}
