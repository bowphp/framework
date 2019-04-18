<?php

namespace Bow\Database\Barry;

use Bow\Database\Collection;
use Bow\Database\Database as DB;
use Bow\Database\Exception\NotFoundException;
use Bow\Support\Str;
use Carbon\Carbon;
use Bow\Database\Barry\Concerns\Relationship;

abstract class Model implements \ArrayAccess, \JsonSerializable
{
    use Relationship;

    /**
     * Enable the timestamps support
     *
     * @var bool
     */
    protected $timestamps = true;

    /**
     * Define the table prefix
     *
     * @var string
     */
    protected $prefix;

    /**
     * Enable the auto increment support
     *
     * @var bool
     */
    protected $auto_increment = true;

    /**
     * Enable the soft deletion
     *
     * @var bool
     */
    protected $soft_delete = false;

    /**
     * Defines the column where the query construct will use for the last query
     *
     * @var string
     */
    protected $latest = 'created_at';

    /**
     * The table columns listing
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * The table columns listing, initilize in first query
     *
     * @var array
     */
    private $original = [];

    /**
     * The date mutation
     *
     * @var array
     */
    protected $dates = [];

    /**
     * The table primary key column name
     *
     * @var string
     */
    protected $primary_key = 'id';

    /**
     * The table primary key type
     *
     * @var string
     */
    protected $primary_key_type = 'int';

    /**
     * The table name
     *
     * @var string
     */
    protected $table;

    /**
     * The connexion name
     *
     * @var string
     */
    protected $connexion;

    /**
     * The query builder instance
     *
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
     * Get all records
     *
     * @param  array $columns
     *
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
     * Get first rows
     *
     * @return static
     */
    public static function first()
    {
        return static::query()->first();
    }

    /**
     * Get last
     *
     * @return static
     */
    public static function latest()
    {
        $query = new static();

        return $query->orderBy($query->latest, 'desc')->first();
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

        $static->whereIn($static->primary_key, $id);

        return count($id) == 1 ? $static->first() : $static->get();
    }

    /**
     * Returns the description of the table
     *
     * @return mixed
     */
    public static function describe()
    {
        return DB::statement('desc '. static::query()->getTable());
    }

    /**
     * Find information and delete it
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
     * Find information by id or throws an
     * exception in data box not found
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
            throw new NotFoundException('No recordings found.');
        }

        return $data;
    }

    /**
     * Create a persist information
     *
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

        if (!array_key_exists($static->primary_key, $data)) {
            if ($static->auto_increment) {
                $id_value = [$static->primary_key => null];

                $data = array_merge($id_value, $data);
            } else {
                if ($static->primary_key_type == 'string') {
                    $data = array_merge([
                        $static->primary_key => ''
                    ], $data);
                }
            }
        }

        $static->setAttributes($data);

        $r = $static->save();

        if ($r == 1) {
            $static->fireEvent('oncreated');
        }

        return $static;
    }

    /**
     * Pagination configuration
     *
     * @param int $n
     * @param int $current
     * @param int $chunk
     *
     * @return Collection
     */
    public static function paginate($n, $current = 0, $chunk = null)
    {
        return static::query()->paginate($n, $current, $chunk);
    }

    /**
     * Allows to associate listener
     *
     * @param callable $cb
     * @throws
     */
    public static function deleted(callable $cb)
    {
        $env = static::formatEventName('ondeleted');

        add_event_once($env, $cb);
    }

    /**
     * Allows to associate a listener
     *
     * @param callable $cb
     * @throws
     */
    public static function created(callable $cb)
    {
        $env = static::formatEventName('oncreated');

        add_event_once($env, $cb);
    }

    /**
     * Allows to associate a listener
     *
     * @param callable $cb
     * @throws
     */
    public static function updated(callable $cb)
    {
        $env = static::formatEventName('onupdate');

        add_event_once($env, $cb);
    }

    /**
     * Initialize the connection
     *
     * @return Builder
     * @throws
     */
    public static function query()
    {
        if (static::$builder instanceof Builder) {
            if (static::$builder->getModel() == static::class) {
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

        if (!is_null($properties['connexion'])) {
            DB::connection($properties['connexion']);
        }

        if (!is_null($properties['prefix'])) {
            $prefix = $properties['prefix'];
        } else {
            $prefix = DB::getConnectionAdapter()->getTablePrefix();
        }

        $table = $prefix.$table;

        static::$builder = new Builder($table, DB::getPdo());

        static::$builder->setPrefix($prefix);

        static::$builder->setModel(static::class);

        return static::$builder;
    }

    /**
     * Retrieves the primary key value
     *
     * @return mixed
     */
    public function getKeyValue()
    {
        if (array_key_exists($this->primary_key, $this->original)) {
            return $this->original[$this->primary_key];
        }

        return null;
    }

    /**
     * Retrieves the primary key
     *
     * @return string
     */
    public function getKey()
    {
        return $this->primary_key;
    }

    /**
     * Save aliase on insert action
     *
     * @return int
     * @throws
     */
    public function save()
    {
        $builder = static::query();

        /**
         * Get the current primary key value
         */
        $primary_key_value = $this->getKeyValue();

        /**
         * If primary key value is null, we are going to start the creation of new
         * row
         */
        if ($primary_key_value == null) {
            /**
             * Insert information in the database
             */
            $r = $builder->insert($this->attributes);

            /**
             * We get a last insertion id value
             */
            $primary_key_value = $builder->getLastInsertId();

            /**
             * Transtype value to the define primary key type
             */
            if ($this->primary_key_type == 'int') {
                $primary_key_value = (int) $primary_key_value;
            } elseif ($this->primary_key_type == 'float') {
                $primary_key_value = (float) $primary_key_value;
            } elseif ($this->primary_key_type == 'double') {
                $primary_key_value = (double) $primary_key_value;
            } else {
                $primary_key_value = (string) $primary_key_value;
            }

            /**
             * Set the primary key value
             */
            $this->attributes[$this->primary_key] = $primary_key_value;

            $this->original = $this->attributes;

            if ($r == 1) {
                $this->fireEvent('oncreated');
            }

            return $r;
        }

        if (!$builder->exists($this->primary_key, $primary_key_value)) {
            return 0;
        }

        $this->original[$this->primary_key] = $primary_key_value;

        $update_data = [];

        foreach ($this->attributes as $key => $value) {
            if (!isset($this->original[$key])
                || $this->original[$key] != $value) {
                $update_data[$key] = $value;
            }
        }

        $r = $builder
            ->where($this->primary_key, $primary_key_value)
            ->update($update_data);

        if ($r == 1) {
            $this->fireEvent('onupdate');
        }

        return $r;
    }

    /**
     * Delete a record
     *
     * @return int
     * @throws
     */
    public function delete()
    {
        $primary_key_value = $this->getKeyValue();

        $builder = static::query();

        if ($primary_key_value == null) {
            return 0;
        }

        if (!$builder->exists($this->primary_key, $primary_key_value)) {
            return 0;
        }

        $r = $builder
            ->where($this->primary_key, $primary_key_value)
            ->delete();

        if ($r == 1) {
            $this->fireEvent('ondeleted');
        }

        return $r;
    }

    /**
     * Used to update the timestamp.
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
     * Get event name
     *
     * @param string $event
     * @return mixed
     */
    private static function formatEventName($event)
    {
        return str_replace('\\', '.', strtolower(static::class)).'.'.$event;
    }

    /**
     * Fire event
     *
     * @param string $event
     */
    private function fireEvent($event)
    {
        $env = $this->formatEventName($event);

        if (emitter()->bound($env)) {
            emitter()->emit($env, $this);
        }
    }

    /**
     * Assign values to class attributes
     *
     * @param array $data
     */
    public function setAttributes(array $data)
    {
        $this->attributes = $data;
    }

    /**
     * Assign a value
     *
     * @param string $key
     * @param string $data
     */
    public function setAttribute($key, $data)
    {
        $this->attributes[$key] = $data;
    }

    /**
     * Set connexion point
     *
     * @param string $connexion
     * @return Builder
     */
    public function setConnexion($connexion)
    {
        $this->connexion = $connexion;

        $builder = static::query();

        return $builder;
    }

    /**
     * Retrieves the list of attributes.
     *
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Allows you to recover an attribute
     *
     * @param  string $name
     * @return mixed|null
     */
    public function getAttribute($name)
    {
        return isset($this->attributes[$name]) ? $this->attributes[$name] : null;
    }

    /**
     * Lists of mutable properties
     *
     * @return array
     */
    private function mutableDateAttributes()
    {
        return array_merge($this->dates, [
            'created_at', 'updated_at', 'expired_at', 'logged_at', 'sigined_at'
        ]);
    }

    /**
     * Get the table name.
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Returns the data
     *
     * @return array
     */
    public function toArray()
    {
        return $this->attributes;
    }

    /**
     * Returns the data
     *
     * @return array
     */
    public function toJson()
    {
        return json_encode($this->attributes);
    }

    /**
     * _offsetSet
     *
     * @param mixed $offset
     * @param mixed $value
     *
     * @see http://php.net/manual/fr/class.arrayaccess.php
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
     * _offsetExists
     *
     * @param mixed $offset
     * @see http://php.net/manual/fr/class.arrayaccess.php
     *
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->attributes[$offset]);
    }

    /**
     * _offsetUnset
     *
     * @param mixed $offset
     *
     * @see http://php.net/manual/fr/class.arrayaccess.php
     */
    public function offsetUnset($offset)
    {
        unset($this->attributes[$offset]);
    }

    /**
     * _offsetGet
     *
     * @param mixed $offset
     * @return mixed|null
     *
     * @see http://php.net/manual/fr/class.arrayaccess.php
     */
    public function offsetGet($offset)
    {
        return isset($this->attributes[$offset]) ? $this->attributes[$offset] : null;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
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
        $attributeExists = isset($this->attributes[$name]);

        if (!$attributeExists && method_exists($this, $name)) {
            return $this->$name()->getResults();
        }

        if (!$attributeExists) {
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

        throw new \BadMethodCallException(
            'method '.$name.' is not defined.',
            E_ERROR
        );
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

        throw new \BadMethodCallException(
            'method '.$name.' is not defined.',
            E_ERROR
        );
    }
}
