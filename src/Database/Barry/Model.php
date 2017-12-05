<?php
namespace Bow\Database\Barry;

use Carbon\Carbon;
use Bow\Support\Str;
use Bow\Database\Collection;
use Bow\Database\Query\Builder;
use Bow\Database\Database as DB;
use Bow\Database\Exception\NotFoundException;

/**
 * Class Model
 *
 * @author  Franck Dakia <dakiafranck@gmail.com>
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
     * @var string
     */
    protected $prefix;

    /**
     * @var bool
     */
    protected $autoIncrement = true;

    /**
     * @var bool
     */
    protected $safeDeleted = false;

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
     * Le nom de la connexion
     *
     * @var string
     */
    protected $connexion;

    /**
     * @var Builder
     */
    protected static $builder;

    /**
     * Model constructor.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->attributes = $attributes;
        $this->original = $attributes;

        static::query();
    }

    /**
     * Rétourne tout les enregistrements
     *
     * @param  array $columns
     * @return \Bow\Database\Collection
     */
    public static function all($columns = [])
    {
        $static = static::query();

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
        return static::query()->first();
    }

    /**
     * find
     *
     * @param  mixed $id
     * @param  array $select
     * @return Collection|static|null
     */
    public static function find($id, $select = ['*'])
    {
        if (! is_array($id)) {
            $id = [$id];
        }

        $static = new static();
        $static->select($select);
        $static->whereIn($static->primaryKey, $id);

        return count($id) == 1 ? $static->first() : $static->get();
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
     * @param  mixed          $id
     * @param  array|callable $select
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
            $data = array_merge(
                $data,
                [
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
                ]
            );
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
     * paginate
     *
     * @param  integer $n       nombre d'element a
     *                          récupérer
     * @param  integer $current la page courrant
     * @param  integer $chunk   le nombre l'élément par groupe que l'on veux
     *                          faire.
     * @return Collection
     */
    public static function paginate($n, $current = 0, $chunk = null)
    {
        return static::query()->paginate($n, $current, $chunk);
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
    private static function query()
    {
        if (static::$builder instanceof Builder) {
            if (static::$builder->getLoadClassName() === static::class) {
                return static::$builder;
            }
        }

        // Reflection action
        $reflection = new \ReflectionClass(static::class);
        $properties = $reflection->getDefaultProperties();

        if ($properties['table'] == null) {
            $parts = explode('\\', static::class);
            $table = end($parts);
            $table = Str::camel(strtolower($table)).'s';
        } else {
            $table = $properties['table'];
        }

        $primaryKey = $properties['primaryKey'];

        if (!is_null($properties['connexion'])) {
            DB::connection($properties['connexion']);
        }

        if (!is_null($properties['prefix'])) {
            $prefix = $properties['prefix'];
        } else {
            $prefix = DB::getConnectionAdapter()->getTablePrefix();
        }

        $table = $prefix.$table;

        static::$builder = new Builder($table, DB::getPdo(), static::class, $primaryKey);
        static::$builder->setPrefix($prefix);

        return static::$builder;
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
        $builder = static::query();
        $primary_key_value = $this->getPrimaryKeyValue();

        if ($primary_key_value != null) {
            if ($builder->exists($this->primaryKey, $primary_key_value)) {
                $this->original[$this->primaryKey] = $primary_key_value;
                $update_data = [];
                
                foreach ($this->attributes as $key => $value) {
                    if ($this->original[$key] == $value) {
                        continue;
                    }
                    $update_data[$key] = $value;
                }

                $r = $builder->where($this->primaryKey, $primary_key_value)->update($update_data);

                $env = str_replace('\\', '.', strtolower(static::class));

                if ($r == 1) {
                    if (emitter()->bound($env.'.onupdate')) {
                        emitter()->emit($env.'.onupdate', $this);
                    }
                }

                return $r;
            }
        }


        $r = $builder->insert($this->attributes);
        $primary_key_value = $builder->getLastInsertId();

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
        $builder = static::query();

        if ($primary_key_value == null) {
            return 0;
        }

        if (!$builder->exists($this->primaryKey, $primary_key_value)) {
            return 0;
        }

        $r = $builder->where($this->primaryKey, $primary_key_value)->delete();
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
     * @param  string $name
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
    private function mutableDateAttributes()
    {
        return array_merge(
            $this->dates,
            [
                'created_at', 'updated_at', 'expired_at', 'logged_at', 'sigined_at'
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
     * @param  string $name
     * @return mixed|null
     */
    public function __get($name)
    {
        if (!isset($this->attributes[$name])) {
            return null;
        }

        if (in_array($name, $this->mutableDateAttributes())) {
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
     * @param  string $name
     * @param  array  $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $builder = static::query();
        if (method_exists($builder, $name)) {
            return call_user_func_array([$builder, $name], $arguments);
        }

        throw new \BadMethodCallException('methode ' . $name . ' n\'est définie.', E_ERROR);
    }

    /**
     * __callStatic
     *
     * @param  string $name
     * @param  array  $arguments
     * @return mixed
     */
    public static function __callStatic($name, $arguments)
    {
        $builder = static::query();
        if (method_exists($builder, $name)) {
            return call_user_func_array([$builder, $name], $arguments);
        }

        throw new \BadMethodCallException('methode ' . $name . ' n\'est définie.', E_ERROR);
    }
}
