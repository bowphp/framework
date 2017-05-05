<?php
namespace Bow\Database;

use Carbon\Carbon;
use Bow\Support\Collection;
use Bow\Database\Database as DB;
use Bow\Exception\ModelException;
use Bow\Exception\QueryBuilderException;
use Bow\Database\QueryBuilder\QueryBuilder;

/**
 * Class Model
 *
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Database
 */
abstract class Model implements \ArrayAccess
{
    /**
     * @var bool
     */
    protected $timestamps = true;

    /**
     * @var bool
     */
    protected $autoincrement = true;

    /**
     * @var array
     */
    private $attributes = [];

    /**
     * @var array
     */
    private $original = [];

    /**
     * @var array
     */
    protected $dates = [];

    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var string
     */
    protected $primaryKeyType = 'int';

    /**
     * Le nom de la table courrente
     *
     * @var string
     */
    protected $table = null;

    /**
     * @var QueryBuilder
     */
    protected static $instance;

    /**
     * Model constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->attributes = $data;
        $this->original = $data;

        static::prepareQueryBuilder();
    }
    /**
     * Rétourne tout les enregistrements
     *
     * @param array $columns
     * @return Collection
     */
    public static function all($columns = [])
    {
        static::prepareQueryBuilder();

        if (count($columns) > 0) {
            static::$instance->select($columns);
        }

        return static::$instance->get();
    }

    /**
     * Permet de récupèrer le première enregistrement
     *
     * @return mixed
     */
    public static function first()
    {
        static::prepareQueryBuilder();
        return static::$instance->take(1)->getOne();
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
        static::prepareQueryBuilder();

        $one = false;

        if (! is_array($id)) {
            $one = true;
            $id = [$id];
        }

        $static = new static();
        static::$instance->select($select);
        static::$instance->whereIn($static->primaryKey, $id);

        return $one ? static::$instance->getOne() : static::$instance->get();
    }

    /**
     * Récuper des informations sur la QueryBuilder ensuite les supprimes dans celle-ci
     *
     * @param mixed $id
     * @param array $select
     *
     * @return Collection|array
     */
    public static function findAndDelete($id, $select = ['*'])
    {
        $data = static::find($id, $select);
        static::$instance->delete();
        return $data;
    }

    /**
     * Lance une execption en case de donnée non trouvé
     *
     * @param mixed $id
     * @param array|callable $select
     * @param callable $callable
     * @return SqlUnity
     *
     * @throws QueryBuilderException
     */
    public static function findOrFail($id, $select = ['*'], callable $callable = null)
    {
        if (is_callable($select)) {
            $callable = $select;
            $select = ['*'];
        }

        $data = static::find($id, $select);

        if (count($data) == 0) {
            if (is_callable($callable)) {
                return $callable();
            }
            throw new QueryBuilderException('Aucune donnée trouver.', E_WARNING);
        }

        return $data;
    }

    /**
     * @param array $data
     * @return self
     */
    public static function create(array $data)
    {
        $static = new static();

        if ($static->timestamps) {
            $data = array_merge($data, [
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }

        if (! array_key_exists($static->primaryKey, $data)) {
            if ($static->autoincrement) {
                $data[$static->primaryKey] = null;
            } else {
                if ($static->primaryKeyType == 'string') {
                    $data[$static->primaryKey] = '';
                }
            }
        }

        $static->setAttributes($data);
        $static->save();

        return $static;
    }

    /**
     * Model where starter
     *
     * @param $column
     * @param string $comp
     * @param null $value
     * @param string $boolean
     * @return QueryBuilder
     */
    public static function where($column, $comp = '=', $value = null, $boolean = 'and')
    {
        static::prepareQueryBuilder();
        return static::$instance->where($column, $comp, $value, $boolean);
    }

    /**
     * paginate
     *
     * @param integer $n nombre d'element a récupérer
     * @param integer $current la page courrant
     * @param integer $chunk le nombre l'élément par groupe que l'on veux faire.
     * @return Collection
     */
    public static function paginate($n, $current = 0, $chunk = null)
    {
        static::prepareQueryBuilder();
        return static::$instance->paginate($n, $current, $chunk);
    }

    /**
     * Permet de compter le nombre d'enregistrement
     *
     * @param string $column
     * @return int
     */
    public static function count($column = '*')
    {
        static::prepareQueryBuilder();
        return static::$instance->count($column);
    }

    /**
     * Permet de rétourne le query builder
     *
     * @return QueryBuilder
     */
    public static function query()
    {
        static::prepareQueryBuilder();
        return static::$instance;
    }

    /**
     * Permet d'initialiser la connection
     */
    private static function prepareQueryBuilder()
    {
        if (static::$instance == null) {
            $reflection = new \ReflectionClass(static::class);
            $properties = $reflection->getDefaultProperties();

            if ($properties['table'] == null) {
                $table = strtolower(end(explode('\\', static::class)));
            } else {
                $table = $properties['table'];
            }

            $primaryKey = $properties['primaryKey'];
            static::$instance = new QueryBuilder($table, DB::getPdo(), static::class, $primaryKey);
        }
    }

    /**
     * Permet de récupérer la valeur de clé primaire
     *
     * @return mixed|null
     */
    private function getPrimaryKeyValue()
    {
        if (array_key_exists($this->primaryKey, $this->original)) {
            return $this->original[$this->primaryKey];
        }

        if (array_key_exists($this->primaryKey, $this->attributes)) {
            return $this->attributes[$this->primaryKey];
        }

        return null;
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

        $primary_key_value = $this->getPrimaryKeyValue();

        if (static::$instance->exists($this->primaryKey, $primary_key_value)) {
            $this->original[$this->primaryKey] = $primary_key_value;
            return static::$instance->where($this->primaryKey, $primary_key_value)->update($this->attributes);
        }

        $n = static::$instance->insert($this->attributes);
        $primary_key_value = static::$instance->getLastInsertId();

        if ($this->primaryKeyType == 'int') {
            $primary_key_value = (int) $primary_key_value;
        } elseif ($this->primaryKeyType == 'float') {
            $primary_key_value = (float) $primary_key_value;
        } elseif ($this->primaryKeyType == 'double') {
            $primary_key_value = (double) $primary_key_value;
        }

        $this->attributes[$this->primaryKey] = $primary_key_value;
        $this->original = $this->attributes;
        return $n;
    }

    /**
     * Permet de supprimer un enregistrement
     *
     * @return int
     */
    public function delete()
    {
        $primary_key_value = $this->getPrimaryKeyValue();

        if (static::$instance->exists($this->primaryKey, $primary_key_value)) {
            return static::$instance->where($this->primaryKey, $primary_key_value)->delete();
        }

        return 0;
    }

    /**
     * Permet d'Assigner des valeurs aux attribues de la classe
     *
     * @param array $data
     */
    public function setAttributes(array $data)
    {
        $this->attributes = $data;
    }

    /**
     * Permet de récupérer la liste des attributes.
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Permet de récupérer un attribue
     *
     * @param string $name
     * @return mixed|null
     */
    public function getAttribute($name)
    {
        return isset($this->attributes[$name]) ? $this->attributes[$name] : null;
    }

    /**
     * Listes des propriétés mutables
     *
     * @return array
     */
    private function mutableAttributes()
    {
        return array_merge(
            $this->dates,
            ['created_at', 'updated_at', 'expired_at', 'logged_at', 'sigined_at']
        );
    }

    /**
     * Permet de retourner les données
     *
     * @return array
     */
    public function toArray()
    {
        return $this->attributes;
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

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            $this->attributes[] = $value;
        } else {
            $this->attributes[$offset] = $value;
        }
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset) {
        return isset($this->attributes[$offset]);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset) {
        unset($this->attributes[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed|null
     */
    public function offsetGet($offset) {
        return isset($this->attributes[$offset]) ? $this->attributes[$offset] : null;
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

        if (in_array($name, $this->mutableAttributes())) {
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
     * __call
     *
     * @param string $method
     * @param array $arguments
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        if (method_exists(static::$instance, $method)) {
            return call_user_func_array([static::$instance, $method], $arguments);
        }

        throw new \BadMethodCallException('methode ' . $method . ' n\'est définie.', E_ERROR);
    }

    /**
     * Facade, implementation de la fonction magic __callStatic de PHP
     *
     * @param string $method Le nom de la method a appelé
     * @param array $arguments  Les arguments a passé à la fonction
     * @return \Bow\Database\QueryBuilder\QueryBuilder|array
     * @throws ModelException
     */
    public static function __callStatic($method, $arguments)
    {
        $static = new static();

        if (method_exists($static, $method)) {
            return call_user_func_array([$static, $method], $arguments);
        }

        throw new \BadMethodCallException('methode ' . $method . ' n\'est définie.', E_ERROR);
    }
}
