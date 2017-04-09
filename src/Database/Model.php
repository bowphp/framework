<?php
namespace Bow\Database;

use Carbon\Carbon;
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
    protected $attributes = [];

    /**
     * @var array
     */
    protected $original = [];

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
        $this->attributes = $data;
        $this->original = $data;
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

        return static::$instance->get();
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

        $table->select($select);

        if (! $one) {
            $table->whereIn(static::$primaryKey, $id);
        }
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
        return static::$instance->take(1)->getOne();
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
     * save aliase sur l'action insert
     *
     * @param array $values Les données a inserer dans la base de donnée.
     * @return int
     * @throws QueryBuilderException
     */
    public function save(array $values = [])
    {
        if (empty($this->attributes)) {
            if (! empty($values)) {
                $this->attributes = $values;
            }
            return static::$instance->insert($this->attributes);
        }

        $primary_key_value = isset($this->original[static::$primaryKey]) ? $this->original[static::$primaryKey] :
            (isset($this->attributes[static::$primaryKey]) ? $this->attributes[static::$primaryKey] : false);

        if ($primary_key_value === false) {
            throw new QueryBuilderException('Cette instance ne possède pas l\'"id" de la table');
        }

        if (! static::$instance->exists(static::$primaryKey, $primary_key_value)) {
            $n = static::$instance->insert($this->attributes);
            $user = static::$instance->where(static::$primaryKey, $primary_key_value)->getOne();
            $this->original = $user->toArray();
            return $n;
        }
        $this->original[static::$primaryKey] = $primary_key_value;
        return static::$instance->where(static::$primaryKey, $primary_key_value)->update($this->attributes);
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
    private static function carbonDates()
    {
        return array_merge(
            static::$dates,
            ['created_at', 'updated_at', 'expired_at', 'logged_at', 'sigined_at']
        );
    }

    /**
     * Permet de format les attribues de type date en classe carbon
     *
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

        $custum_dates_lists = static::carbonDates();

        if (method_exists($child, 'customDate')) {
            $custum_dates_lists = array_merge($custum_dates_lists, $child->customDate());
        }

        foreach($collection as $value) {
            if (! is_object($value)) {
                continue;
            }
            foreach($value as $key => $content) {
                if (in_array($key, $custum_dates_lists)) {
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
     * Facade, implementation de la fonction magic __callStatic de PHP
     *
     * @param string $method Le nom de la method a appelé
     * @param array $args    Les arguments a passé à la fonction
     * @throws ModelException
     * @return \Bow\Database\QueryBuilder|array
     */
    public static function __callStatic($method, $args)
    {
        static::initilaizeQueryBuilder();

        if (method_exists($self = new static(), $method)) {
            if (method_exists(static::$instance, $method)) {
                throw new ModelException($method . ' ne peut pas être utiliser comme fonction d\'aliase.', E_ERROR);
            }
            return call_user_func_array([$self, $method], $args);
        }

        // Lancement de l'execution des fonctions liée a l'instance de la classe Table
        if (method_exists(static::$instance, $method)) {
            $collection = call_user_func_array([static::$instance, $method], $args);
            return static::carbornize($collection, $method, $self);
        }

        throw new ModelException('methode ' . $method . ' n\'est définie.', E_ERROR);
    }

    /**
     * __get
     *
     * @param string $name
     * @return mixed|null
     */
    public function __get($name)
    {
        if (! isset($this->attributes[$name])) {
            return null;
        }

        if (in_array($name, static::carbonDates())) {
            return new Carbon($this->attributes[$name]);
        }

        return $this->attributes[$name];
    }

    /**
     * __set
     *
     * @param string $name
     * @param $value
     */
    public function __set($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    /**
     * __toString
     *
     * @return string
     */
    public function __toString()
    {
        return json_encode($this->attributes);
    }

    /**
     * Permet de formater le donnée en json quand on appele la
     * fonction json_encode sur une instance.
     *
     * @return string
     */
    public function jsonSerialize()
    {
        return $this->attributes;
    }
}
