<?php

namespace Bow\Database\Barry;

use Bow\Database\Collection;
use Bow\Database\Database as DB;
use Bow\Database\Exception\NotFoundException;
use Bow\Database\Barry\Concerns\Relationship;
use Bow\Database\Barry\Traits\EventTrait;
use Bow\Database\Barry\Traits\ArrayAccessTrait;
use Bow\Support\Str;
use Carbon\Carbon;

abstract class Model implements \ArrayAccess, \JsonSerializable
{
    use Relationship;
    use EventTrait;
    use ArrayAccessTrait;

    /**
     * The hidden field
     *
     * @var array
     */
    protected $hidden = [];

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
     * Enable the autoincrement support
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
     * The table columns listing, initialize in first query
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
     * The casts mutation
     *
     * @var array
     */
    protected $casts = [];

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
     * The connection name
     *
     * @var string
     */
    protected $connection;

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

        $this->table = static::query()->getTable();
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
        $model = static::query();

        if (count($columns) > 0) {
            $model->select($columns);
        }

        return $model->get();
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
        $id = (array) $id;

        $model = new static();
        $model->select($select);
        $model->whereIn($model->primary_key, $id);

        if (count($id) != 1) {
            return $model->get();
        }

        $result = $model->first();

        return $result;
    }

    /**
     * Find by column name
     *
     * @param string $column
     * @param mixed $value
     * @return Collection|null
     */
    public static function findBy($column, $value)
    {
        $model = new static();
        $model->where($column, $value);

        return $model->get();
    }

    /**
     * Returns the description of the table
     *
     * @return mixed
     */
    public static function describe()
    {
        return DB::statement('desc ' . static::query()->getTable());
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
        $model = static::find($id, $select);

        $model->delete();

        return $model;
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

        if (is_null($data)) {
            throw new NotFoundException('No recordings found at ' . $id . '.');
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
        $model = new static();

        if ($model->timestamps) {
            $data = array_merge($data, [
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }

        if (!array_key_exists($model->primary_key, $data)) {
            if ($model->auto_increment) {
                $id_value = [$model->primary_key => null];

                $data = array_merge($id_value, $data);
            } else {
                if ($model->primary_key_type == 'string') {
                    $data = array_merge([
                        $model->primary_key => ''
                    ], $data);
                }
            }
        }

        $model->setAttributes($data);

        $r = $model->save();

        if ($r == 1) {
            $model->fireEvent('oncreated');
        }

        return $model;
    }

    /**
     * Pagination configuration
     *
     * @param int $page_number
     * @param int $current
     * @param int $chunk
     * @return Collection
     */
    public static function paginate($page_number, $current = 0, $chunk = null)
    {
        return static::query()->paginate($page_number, $current, $chunk);
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

        listen_event_once($env, $cb);
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

        listen_event_once($env, $cb);
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

        listen_event_once($env, $cb);
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
            $table = Str::snake(end($parts)) . 's';
        } else {
            $table = $properties['table'];
        }

        if (!is_null($properties['connection'])) {
            DB::connection($properties['connection']);
        }

        if (!is_null($properties['prefix'])) {
            $prefix = $properties['prefix'];
        } else {
            $prefix = DB::getConnectionAdapter()->getTablePrefix();
        }

        // Set the table prefix
        $table = $prefix . $table;

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
     * Save aliases on insert action
     *
     * @return int
     * @throws
     */
    public function save()
    {
        $model = static::query();

        /**
         * Get the current primary key value
         */
        $primary_key_value = $this->getKeyValue();

        // If primary key value is null, we are going to start the creation of new row
        if ($primary_key_value == null) {
            // Insert information in the database
            $row_affected = $model->insert($this->attributes);

            // We get a last insertion id value
            $primary_key_value = $model->getLastInsertId();

            // Set the primary key value
            $this->attributes[$this->primary_key] = $primary_key_value;
            $this->original = $this->attributes;

            if ($row_affected == 1) {
                $this->fireEvent('oncreated');
            }

            return $row_affected;
        }

        $primary_key_value = $this->transtypeKeyValue($primary_key_value);

        // Check the existent in database
        if (!$model->exists($this->primary_key, $primary_key_value)) {
            return 0;
        }

        // We set the primary key value
        $this->original[$this->primary_key] = $primary_key_value;

        $update_data = [];

        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                $update_data[$key] = $value;
            }
        }

        // When the update data is empty, we load the original data
        if (count($update_data) == 0) {
            $update_data = $this->original;
        }

        $row_affected = $model->where($this->primary_key, $primary_key_value)->update($update_data);

        if ($row_affected == 1) {
            $this->fireEvent('onupdate');
        }

        return $row_affected;
    }

    /**
     * Transtype the primary key value
     *
     * @param mixed $primary_key_value
     * @return mixed
     */
    private function transtypeKeyValue($primary_key_value)
    {
        // Transtype value to the define primary key type
        if ($this->primary_key_type == 'int') {
            $primary_key_value = (int) $primary_key_value;
        } elseif ($this->primary_key_type == 'float') {
            $primary_key_value = (float) $primary_key_value;
        } elseif ($this->primary_key_type == 'double') {
            $primary_key_value = (float) $primary_key_value;
        } else {
            $primary_key_value = (string) $primary_key_value;
        }

        return $primary_key_value;
    }

    /**
     * Delete a record
     *
     * @return int
     * @throws
     */
    public function update(array $attribute)
    {
        $primary_key_value = $this->getKeyValue();

        $model = static::query();

        if ($primary_key_value == null) {
            return 0;
        }

        // We set the primary key value
        $this->original[$this->primary_key] = $primary_key_value;

        $update_data = $attribute;

        if (count($this->original) > 0) {
            $update_data = [];
            foreach ($attribute as $key => $value) {
                if (array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                    $update_data[$key] = $value;
                }
            }
        }

        // When the update data is empty, we load the original data
        if (count($update_data) == 0) {
            $this->fireEvent('onupdate');
            return true;
        }

        $row_affected = $model->where($this->primary_key, $primary_key_value)->update($update_data);

        if ($row_affected == 1) {
            $this->fireEvent('onupdate');
        }

        return $row_affected;
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

        $model = static::query();

        if ($primary_key_value == null) {
            return 0;
        }

        if (!$model->exists($this->primary_key, $primary_key_value)) {
            return 0;
        }

        $deleted = $model->where($this->primary_key, $primary_key_value)->delete();

        if ($deleted) {
            $this->fireEvent('ondeleted');
        }

        return $deleted;
    }

    /**
     * Delete Active Record by column name
     *
     * @param string $column
     * @param mixed $value
     * @return int
     */
    public static function deleteBy($column, $value)
    {
        $model = static::query();

        $deleted = $model->where($column, $value)->delete();

        return $deleted;
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
     * Assign values to class attributes
     *
     * @param array $attributes
     */
    public function setAttributes(array $attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * Assign a value
     *
     * @param string $key
     * @param string $value
     */
    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Set connection point
     *
     * @param string $connection
     * @return Builder
     */
    public function setConnection($connection)
    {
        $this->connection = $connection;

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
     * @param  string $key
     * @return mixed|null
     */
    public function getAttribute($key)
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Lists of mutable properties
     *
     * @return array
     */
    private function mutableDateAttributes()
    {
        return array_merge($this->dates, [
            'created_at', 'updated_at', 'expired_at', 'logged_at', 'signed_at'
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
        $data = [];

        foreach ($this->attributes as $key => $value) {
            if (!in_array($key, $this->hidden)) {
                $data[$key] = $value;
            }
        }

        return json_encode($data);
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        $data = [];

        foreach ($this->attributes as $key => $value) {
            if (!in_array($key, $this->hidden)) {
                $data[$key] = $value;
            }
        }

        return $data;
    }

    /**
     * __get
     *
     * @param  string $name
     * @return mixed|null
     */
    public function __get($name)
    {
        $attribute_exists = isset($this->attributes[$name]);

        if (!$attribute_exists && method_exists($this, $name)) {
            return $this->$name()->getResults();
        }

        if (!$attribute_exists) {
            return null;
        }

        if (in_array($name, $this->mutableDateAttributes())) {
            return new Carbon($this->attributes[$name]);
        }

        if (array_key_exists($name, $this->casts)) {
            $type = $this->casts[$name];
            $value = $this->attributes[$name];
            if ($type === "date") {
                return new Carbon($value);
            }
            if ($type === "int") {
                return (int) $value;
            }
            if ($type === "float") {
                return (float) $value;
            }
            if ($type === "double") {
                return (double) $value;
            }
            if ($type === "json") {
                if (is_array($value)) {
                    return (object) $value;
                }
                if (is_object($value)) {
                    return (object) $value;
                }
                return json_decode(
                    $value,
                    false,
                    512,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_IGNORE
                );
            }
            if ($type === "array") {
                if (is_array($value)) {
                    return (array) $value;
                }
                if (is_object($value)) {
                    return (array) $value;
                }
                return json_decode(
                    $value,
                    true,
                    512,
                    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_IGNORE
                );
            }
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
        return $this->toJson();
    }

    /**
     * __call
     *
     * @param  string $name
     * @param  array  $arguments
     * @return mixed
     */
    public function __call($name, array $arguments)
    {
        $model = static::query();

        if (method_exists($model, $name)) {
            return call_user_func_array([$model, $name], $arguments);
        }

        throw new \BadMethodCallException(
            'method ' . $name . ' is not defined.',
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
        $model = static::query();

        if (method_exists($model, $name)) {
            return call_user_func_array([$model, $name], $arguments);
        }

        throw new \BadMethodCallException(
            'method ' . $name . ' is not defined.',
            E_ERROR
        );
    }
}
