<?php
namespace Bow\Database;

use Carbon\Carbon;
use Bow\Support\Collection;
use Bow\Database\Database as DB;
use Bow\Exception\QueryBuilderException;
use Bow\Database\QueryBuilder\QueryBuilder;

/**
 * Class Model
 *
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Database
 */
abstract class Model implements \ArrayAccess, \JsonSerializable
{
    /**
     * @var array
     */
    protected $describeOrder = [];

    /**
     * @var bool
     */
    protected $timestamps = true;

    /**
     * @var bool
     */
    protected $autoIncrement = true;

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
    private static $builder;

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
            self::$builder->select($columns);
        }

        return self::$builder->get();
    }

    /**
     * Permet de récupèrer le première enregistrement
     *
     * @return mixed
     */
    public static function first()
    {
        return self::query()->take(1)->first();
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
        self::$builder->select($select);
        self::$builder->whereIn($static->primaryKey, $id);

        return $one ? self::$builder->first() : self::$builder->get();
    }

    /**
     * Permet de retourner le description de la table
     */
    public static function describe()
    {
        return Database::select('desc '. self::query()->getTable());
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
        self::$builder->delete();

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

            abort(404);
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
            if ($static->autoIncrement) {
                $id_value = [$static->primaryKey => null];
                $data = array_merge($id_value, $data);
            } else {
                if ($static->primaryKeyType == 'string') {
                    $data = array_merge([$static->primaryKey => ''], $data);
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
        return self::query()->where($column, $comp, $value, $boolean);
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
        return self::query()->paginate($n, $current, $chunk);
    }

    /**
     * Permet de compter le nombre d'enregistrement
     *
     * @param string $column
     * @return int
     */
    public static function count($column = '*')
    {
        return self::query()->count($column);
    }

    /**
     * Permet de rétourne le query builder
     *
     * @return QueryBuilder
     */
    public static function query()
    {
        static::prepareQueryBuilder();
        return self::$builder;
    }

    /**
     * Permet de faire une réquete avec la close DISTINCT
     *
     * @param string $column
     * @return QueryBuilder
     */
    public static function distinct($column)
    {
        return self::query()->select(['distinct '.$column]);
    }

    /**
     * @param array $select
     * @return QueryBuilder
     */
    public static function select(array $select)
    {
        return self::query()->select($select);
    }

    /**
     * @param string $table
     * @return QueryBuilder
     */
    public static function join($table)
    {
        return self::query()->join($table);
    }

    /**
     * @param string $column
     * @return int
     */
    public static function increment($column)
    {
        return self::query()->increment($column);
    }

    /**
     * @param string $column
     * @return int
     */
    public static function decrement($column)
    {
        return self::query()->decrement($column);
    }

    /**
     * @return bool
     */
    public static function truncate()
    {
        return self::query()->truncate();
    }

    /**
     * @param string $column
     * @param mixed $value
     * @return bool
     */
    public static function exists($column = null, $value = null)
    {
        return self::query()->exists($column, $value);
    }

    /**
     * @return mixed
     */
    public static function last()
    {
        return self::query()->last();
    }

    /**
     * @param $limit
     * @return QueryBuilder
     */
    public static function take($limit)
    {
        return self::query()->take($limit);
    }

    /**
     * Permet d'associer listerner
     *
     * @param callable $cb
     */
    public static function deleted(callable $cb)
    {
        $env = str_replace('\\', '.', strtolower(static::class));

        if (! emitter()->binded($env.'.ondelete')) {
            event($env.'.ondelete', $cb);
        }
    }

    /**
     * Permet d'associer un listerner
     *
     * @param callable $cb
     */
    public static function created(callable $cb)
    {
        $env = str_replace('\\', '.', strtolower(static::class));

        if (! emitter()->binded($env.'.oncreated')) {
            event($env . '.oncreate', $cb);
        }
    }

    /**
     * Permet d'associer un listerner
     *
     * @param callable $cb
     */
    public static function updated(callable $cb)
    {
        $env = str_replace('\\', '.', strtolower(static::class));

        if (! emitter()->binded($env.'.onupdate')) {
            event($env.'.onupdate', $cb);
        }
    }

    /**
     * Permet d'initialiser la connection
     *
     * @return void
     */
    private static function prepareQueryBuilder()
    {
        if (self::$builder instanceof QueryBuilder) {
            if (self::$builder->getLoadClassName() === static::class) {
                return;
            }
        }

        $reflection = new \ReflectionClass(static::class);
        $properties = $reflection->getDefaultProperties();

        if ($properties['table'] == null) {
            $table = strtolower(end(explode('\\', static::class)));
        } else {
            $table = $properties['table'];
        }

        $primaryKey = $properties['primaryKey'];
        $table = DB::getConnectionAdapter()->getTablePrefix().$table;

        self::$builder = new QueryBuilder($table, DB::getPdo(), static::class, $primaryKey);
    }

    /**
     * Permet de récupérer la valeur de clé primaire
     *
     * @return mixed
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
     * @return int
     * @throws QueryBuilderException
     */
    public function save()
    {
        $primary_key_value = $this->getPrimaryKeyValue();

        if ($primary_key_value != null) {
            if (self::$builder->exists($this->primaryKey, $primary_key_value)) {

                $this->original[$this->primaryKey] = $primary_key_value;
                $r = self::$builder->where($this->primaryKey, $primary_key_value)->update($this->attributes);

                $env = str_replace('\\', '.', strtolower(static::class));

                if (emitter()->binded($env.'.onupdate')) {
                    emitter()->emit($env.'.onupdate');
                }

                return $r;
            }
        }


        $n = self::$builder->insert($this->attributes);
        $primary_key_value = self::$builder->getLastInsertId();

        if ($this->primaryKeyType == 'int') {
            $primary_key_value = (int) $primary_key_value;
        } elseif ($this->primaryKeyType == 'float') {
            $primary_key_value = (float) $primary_key_value;
        } elseif ($this->primaryKeyType == 'double') {
            $primary_key_value = (double) $primary_key_value;
        }

        $this->attributes[$this->primaryKey] = $primary_key_value;
        $this->original = $this->attributes;

        if (emitter()->binded(strtolower(static::class).'.oncreate')) {
            emitter()->emit(strtolower(static::class).'.oncreate');
        }

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

        if ($primary_key_value == null) {
            return 0;
        }

        if (! self::$builder->exists($this->primaryKey, $primary_key_value)) {
            return 0;
        }

        $r = self::$builder->where($this->primaryKey, $primary_key_value)->delete();
        $env = str_replace('\\', '.', strtolower(static::class));

        if ($r !== 0 && emitter()->binded($env.'.ondelete')) {
            emitter()->emit($env.'.ondelete');
        }

        return $r;
    }

    /**
     * Permet de mettre le timestamp à jour.
     *
     * @return bool
     */
    public function touch()
    {
        if (! $this->timestamps) {
            return false;
        }

        $this->setAttribute('updated_at', date('Y-m-d H:i:s'));
        return (bool) $this->save();
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
     * Permet d'Assigner une valeur
     *
     * @param string $key
     * @param array $data
     */
    public function setAttribute($key, $data)
    {
        $this->attributes[$key] = $data;
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
            [
                'created_at', 'updated_at',
                'expired_at', 'logged_at',
                'sigined_at'
            ]
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
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
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
    public function offsetExists($offset)
    {
        return isset($this->attributes[$offset]);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        return isset($this->attributes[$offset]) ? $this->attributes[$offset] : null;
    }

    /**
     * @inheritDoc
     */
    function jsonSerialize()
    {
        return $this->attributes;
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
        if (method_exists(self::query(), $method)) {
            return call_user_func_array([self::query(), $method], $arguments);
        }

        throw new \BadMethodCallException('methode ' . $method . ' n\'est définie.', E_ERROR);
    }
}
