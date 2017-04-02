<?php
namespace Bow\Database;

use Bow\Support\Collection;
use Bow\Database\Database as DB;
use Bow\Exception\ModelException;
use Bow\Exception\QueryBuilderException;

/**
 * Class Model
 *
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Database
 */
abstract class Model extends QueryBuilder
{
    /**
     * @var array
     */
    protected static $attributes = [];

    /**
     * @var array
     */
    protected static $dates = [];

    /**
     * @var string
     */
    protected static $primaryKey = 'id';

    /**
     * Le nom de la table courrente
     *
     * @var string
     */
    protected static $table = null;

    /**
     * Model constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        static::$attributes = $data;
        static::initilaizeQueryBuilder();
        parent::__construct(static::$table, DB::getPdo(), static::class);
    }
    /**
     * Rétourne tout les enregistrements
     *
     * @param array $columns
     * @return Collection
     */
    public static function all($columns = [])
    {
        static::initilaizeQueryBuilder();

        if (count($columns) > 0) {
            static::$instance->select = '`' . implode('`, `', $columns) . '`';
        }

        return static::get();
    }

    /**
     * find
     *
     * @param mixed $id
     * @param array $select
     * @return Collection|SqlUnity
     * @throws QueryBuilderException
     */
    public static function find($id, $select = ['*'])
    {
        static::initilaizeQueryBuilder();
        $table = static::$instance;
        $one = false;

        if (! is_array($id)) {
            $one = true;
            $id = [$id];
        }

        $table->whereIn(static::$primaryKey, $id);
        $table->select($select);

        return $one ? static::first() : $table->get();
    }

    /**
     * Permet de récupèrer le première enregistrement
     *
     * @return mixed
     */
    public static function first()
    {
        static::initilaizeQueryBuilder();
        return static::take(1)->getOne();
    }

    /**
     * Action first, récupère le première enregistrement
     *
     * @return mixed
     */
    public function last()
    {
        $where = $this->where;
        $whereData = $this->whereDataBind;

        // On compte le tout.
        $c = $this->count();

        $this->where = $where;
        $this->whereDataBind = $whereData;

        return $this->jump($c - 1)->take(1)->getOne();
    }

    /**
     * Récuper des informations sur la QueryBuilder ensuite les supprimes dans celle-ci
     *
     * @param Callable $cb La fonction de rappel qui si definir vous offre en parametre
     *                     Les données récupés et le nombre d'élément supprimé.
     *
     * @return Collection|array
     */
    public static function findAndDelete($id, $cb = null)
    {
        static::initilaizeQueryBuilder();
        $data = static::find($id);
        static::$instance->delete();
        if (is_callable($cb)) {
            return call_user_func_array($cb, [$data]);
        }
        return $data;
    }

    /**
     * Lance une execption en case de donnée non trouvé
     *
     * @param int|string $id
     * @return SqlUnity
     *
     * @throws QueryBuilderException
     */
    public static function findOrFail($id)
    {
        $data = static::find($id);
        if (count($data) == 0) {
            throw new QueryBuilderException('Aucune donnée trouver.', E_WARNING);
        }
        return $data;
    }

    /**
     * Lists des fonctions static
     *
     * @return array
     */
    private static function avalableMethods()
    {
        return ['get', 'first', 'find', 'all'];
    }

    /**
     *
     *
     * @return array
     */
    private static function avalableFields()
    {
        return array_merge(
            static::$dates,
            ['created_at', 'updated_at', 'expired_at', 'logged_at', 'sigined_at']
        );
    }

    /**
     * @param mixed $collection
     * @param string $method
     * @param mixed $child
     * @return array
     */
    private static function carbornize($collection, $method, $child)
    {
        if (! in_array($method, static::avalableMethods())) {
            return $collection;
        }

        if (is_array($collection)) {
            $collection = [$collection];
        }

        $custumFieldsLists = static::avalableFields();

        if (method_exists($child, 'customDate')) {
            $custumFieldsLists = array_merge($custumFieldsLists, $child->customDate());
        }

        foreach($collection as $value) {
            if (! is_object($value)) {
                continue;
            }
            foreach($value as $key => $content) {
                if (in_array($key, $custumFieldsLists)) {
                    $value->$key = new \Carbon\Carbon($content);
                }
            }
        }

        if (count($collection) == 1) {
            if ($method == 'getOne' || preg_match('/^find/', $method)) {
                $collection = end($collection);
            }
        }

        return $collection;
    }

    /**
     * Facade, implementation de la fonction magic __callStatic de PHP
     *
     * @param string $method Le nom de la method a appelé
     * @param array $args    Les arguments a passé à la fonction
     * @throws ModelException
     * @return \Bow\Database\QueryBuilder|array
     */
    public static function __callStatic($method, $args)
    {
        $scope = 'scope' . ucfirst($method);
        static::initilaizeQueryBuilder();
        $query_build = static::$instance;
        /**
         * Lancement de l'execution des fonctions aliase définir dans les classe
         * héritant de la classe Model.
         *
         * Les classes  definir définir avec la montion scope.
         */
        if (method_exists($child = new static, $scope)) {
            if (method_exists($query_build, $method)) {
                throw new ModelException($method . ' ne peut pas être utiliser comme fonction d\'aliase.', E_ERROR);
            }
            return call_user_func_array([$child, $scope], $args);
        }

        /**
         * Lancement de l'execution des fonctions liée a l'instance de la classe Table
         */
        if (method_exists($query_build, $method)) {
            $collection = call_user_func_array([$query_build, $method], $args);
            return static::carbornize($collection, $method, $child);
        }

        throw new ModelException('methode ' . $method . ' n\'est définie.', E_ERROR);
    }

    /**
     * Permet d'initialiser la connection
     */
    private static function initilaizeQueryBuilder()
    {
        if (static::$table == null) {
            static::$table = strtolower(static::class);
        }
        if (static::$instance == null) {
            static::make(static::$table, DB::getPdo(), static::class);
        }
    }

    /**
     * __get
     *
     * @param string $name
     * @return mixed|null
     */
    public function __get($name)
    {
        if (! isset(static::$attributes[$name])) {
            return null;
        }

        return static::$attributes[$name];
    }

    /**
     * __set
     *
     * @param string $name
     * @param $value
     */
    public function __set($name, $value)
    {
        static::$attributes[$name] = $value;
    }
}
