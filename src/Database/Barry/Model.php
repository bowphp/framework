<?php
namespace Bow\Database\Barry;

use Bow\Database\Exception\NotFoundException;
use Bow\Support\Str;
use Carbon\Carbon;
use Bow\Database\Collection;
use Bow\Database\Database as DB;
use Bow\Database\Query\Builder;

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
    protected $table;

    /**
     * @var Builder
     */
    protected static $builder;

    /**
     * Model constructor.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->attributes = $data;
        $this->original = $data;

        static::newBuilder();
    }

    /**
     * Rétourne tout les enregistrements
     *
     * @param array $columns
     * @return \Bow\Database\Collection
     */
    public static function all($columns = [])
    {
        $static = static::newBuilder();

        if (count($columns) > 0) {
            $static->select($columns);
        }

        return $static->get();
    }

    /**
     * @return \Bow\Database\SqlUnity|null
     */
    public static function first()
    {
        return static::query()->take(1)->first();
    }

    /**
     * find
     *
     * @param mixed $id
     * @param array $select
     * @return Collection|static|null
     */
    public static function find($id, $select = ['*'])
    {
        static::newBuilder();

        $one = false;

        if (!is_array($id)) {
            $one = true;
            $id = [$id];
        }

        $static = new static();
        $static->select($select);
        $static->whereIn($static->primaryKey, $id);

        return $one ? $static->first() : $static->get();
    }

    /**
     * Permet de retourner le description de la table
     */
    public static function describe()
    {
        return DB::select('desc '. static::query()->getTable());
    }

    /**
     * Récuper des informations sur la Builder ensuite les supprimes dans celle-ci
     *
     * @param mixed $id
     * @param array $select
     *
     * @return Collection|static|null
     */
    public static function findAndDelete($id, $select = ['*'])
    {
        $data = static::find($id, $select);
        static::$builder->delete();

        return $data;
    }

    /**
     * Lance une execption en case de donnée non trouvé
     *
     * @param mixed $id
     * @param array|callable $select
     * @return static
     * @throws NotFoundException
     */
    public static function findOrFail($id, $select = ['*'])
    {
        $data = static::find($id, $select);

        if (is_null($data) || count($data) == 0) {
            throw new NotFoundException('Aucun enrégistrement trouvé');
        }

        return $data;
    }

    /**
     * @param array $data
     * @return static
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

        if (!array_key_exists($static->primaryKey, $data)) {
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
     * @return Builder
     */
    public static function where($column, $comp = '=', $value = null, $boolean = 'and')
    {
        return static::query()->where($column, $comp, $value, $boolean);
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
        return static::query()->paginate($n, $current, $chunk);
    }

    /**
     * Permet de compter le nombre d'enregistrement
     *
     * @param string $column
     * @return int
     */
    public static function count($column = '*')
    {
        return static::query()->count($column);
    }

    /**
     * Permet de rétourne le query builder
     *
     * @return Builder
     */
    public static function query()
    {
        return static::newBuilder();
    }

    /**
     * Permet de faire une réquete avec la close DISTINCT
     *
     * @param string $column
     * @return Builder
     */
    public static function distinct($column)
    {
        return static::query()->select(['distinct '.$column]);
    }

    /**
     * @param array $select
     * @return Builder
     */
    public static function select(array $select)
    {
        return static::query()->select($select);
    }

    /**
     * @param string $table
     * @return Builder
     */
    public static function join($table)
    {
        return static::query()->join($table);
    }

    /**
     * @param string $column
     * @return int
     */
    public static function increment($column)
    {
        return static::query()->increment($column);
    }

    /**
     * @param string $column
     * @return int
     */
    public static function decrement($column)
    {
        return static::query()->decrement($column);
    }

    /**
     * @return bool
     */
    public static function truncate()
    {
        return static::query()->truncate();
    }

    /**
     * Permet d'associer listerner
     *
     * @param callable $cb
     */
    public static function deleted(callable $cb)
    {
        $env = str_replace('\\', '.', strtolower(static::class));
        add_event_once($env.'.ondelete', $cb);
    }

    /**
     * Permet d'associer un listerner
     *
     * @param callable $cb
     */
    public static function created(callable $cb)
    {
        $env = str_replace('\\', '.', strtolower(static::class));
        add_event_once($env . '.oncreate', $cb);
    }

    /**
     * Permet d'associer un listerner
     *
     * @param callable $cb
     */
    public static function updated(callable $cb)
    {
        $env = str_replace('\\', '.', strtolower(static::class));
        add_event_once($env.'.onupdate', $cb);
    }

    /**
     * Permet d'initialiser la connection
     *
     * @return Builder
     */
    private static function newBuilder()
    {
        if (static::$builder instanceof Builder) {
            if (static::$builder->getLoadClassName() === static::class) {
                return self::$builder;
            }
        }

        $reflection = new \ReflectionClass(static::class);
        $properties = $reflection->getDefaultProperties();

        if ($properties['table'] == null) {
            $parts = explode('\\', static::class);
            $class_name = end($parts);
            $table = Str::camel(strtolower($class_name)).'s';
        } else {
            $table = $properties['table'];
        }

        $primaryKey = $properties['primaryKey'];
        $table = DB::getConnectionAdapter()->getTablePrefix().$table;

        return static::$builder = new Builder($table, DB::getPdo(), static::class, $primaryKey);
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
     */
    public function save()
    {
        $primary_key_value = $this->getPrimaryKeyValue();

        if ($primary_key_value != null) {
            if (static::$builder->exists($this->primaryKey, $primary_key_value)) {

                $this->original[$this->primaryKey] = $primary_key_value;
                $r = static::$builder->where($this->primaryKey, $primary_key_value)->update($this->attributes);

                $env = str_replace('\\', '.', strtolower(static::class));

                if ($r == 1) {
                    if (emitter()->bound($env.'.onupdate')) {
                        emitter()->emit($env.'.onupdate', $this);
                    }
                }

                return $r;
            }
        }


        $r = static::$builder->insert($this->attributes);
        $primary_key_value = static::$builder->getLastInsertId();

        if ($this->primaryKeyType == 'int') {
            $primary_key_value = (int) $primary_key_value;
        } elseif ($this->primaryKeyType == 'float') {
            $primary_key_value = (float) $primary_key_value;
        } elseif ($this->primaryKeyType == 'double') {
            $primary_key_value = (double) $primary_key_value;
        }

        $this->attributes[$this->primaryKey] = $primary_key_value;
        $this->original = $this->attributes;

        if ($r == 1) {
            if (emitter()->bound(strtolower(static::class).'.oncreate')) {
                emitter()->emit(strtolower(static::class).'.oncreate');
            }
        }

        return $r;
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

        if (!static::$builder->exists($this->primaryKey, $primary_key_value)) {
            return 0;
        }

        $r = static::$builder->where($this->primaryKey, $primary_key_value)->delete();
        $env = str_replace('\\', '.', strtolower(static::class));

        if ($r == 1 && emitter()->bound($env.'.ondelete')) {
            emitter()->emit($env.'.ondelete', $this);
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
        if (!$this->timestamps) {
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
     * @param string $data
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
        if (!isset($this->attributes[$name])) {
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
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        if (method_exists(static::query(), $name)) {
            return call_user_func_array([static::query(), $name], $arguments);
        }

        throw new \BadMethodCallException('methode ' . $name . ' n\'est définie.', E_ERROR);
    }

    /**
     * __callStatic
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        if (method_exists(static::query(), $name)) {
            return call_user_func_array([static::query(), $name], $arguments);
        }

        throw new \BadMethodCallException('methode ' . $name . ' n\'est définie.', E_ERROR);
    }
}
