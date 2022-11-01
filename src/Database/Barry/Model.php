<?php declare(strict_types=1);

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
    use Relationship, EventTrait, ArrayAccessTrait;

    /**
     * The hidden field
     *
     * @var array
     */
    protected array $hidden = [];

    /**
     * Enable the timestamps support
     *
     * @var bool
     */
    protected bool $timestamps = true;

    /**
     * Define the table prefix
     *
     * @var string
     */
    protected string $prefix;

    /**
     * Enable the autoincrement support
     *
     * @var bool
     */
    protected bool $auto_increment = true;

    /**
     * Enable the soft deletion
     *
     * @var bool
     */
    protected bool $soft_delete = false;

    /**
     * Defines the column where the query construct will use for the last query
     *
     * @var string
     */
    protected string $latest = 'created_at';

    /**
     * Defines the created_at column name
     *
     * @var string
     */
    protected string $created_at = 'created_at';

    /**
     * Defines the created_at column name
     *
     * @var string
     */
    protected string $updated_at = 'updated_at';

    /**
     * The table columns listing
     *
     * @var array
     */
    protected array $attributes = [];

    /**
     * The table columns listing, initialize in first query
     *
     * @var array
     */
    private array $original = [];

    /**
     * The date mutation
     *
     * @var array
     */
    protected array $dates = [];

    /**
     * The casts mutation
     *
     * @var array
     */
    protected array $casts = [];

    /**
     * The table primary key column name
     *
     * @var string
     */
    protected string $primary_key = 'id';

    /**
     * The table primary key type
     *
     * @var string
     */
    protected string $primary_key_type = 'int';

    /**
     * The table name
     *
     * @var string
     */
    protected string $table;

    /**
     * The connection name
     *
     * @var string
     */
    protected string $connection;

    /**
     * The query builder instance
     *
     * @var Builder
     */
    protected static ?Builder $builder = null;

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
    public static function all(array $columns = [])
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
     * @return Model
     */
    public static function first(): ?Model
    {
        return static::query()->first();
    }

    /**
     * Get latest
     *
     * @return Model
     */
    public static function latest(): ?Model
    {
        $query = new static;

        return $query->orderBy($query->latest, 'desc')->first();
    }

    /**
     * find
     *
     * @param  mixed $id
     * @param  array $select
     * @return Collection|static|null
     */
    public static function find(
        int|string|array $id,
        array $select = ['*']
    ): Collection|Model|null {
        $id = (array) $id;

        $model = new static;
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
     * @return Collection
     */
    public static function findBy(string $column, mixed $value): Collection
    {
        $model = new static;
        $model->where($column, $value);

        return $model->get();
    }

    /**
     * Returns the description of the table
     *
     * @return bool
     */
    public static function describe(): bool
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
    public static function findAndDelete(
        int | string | array $id,
        array $select = ['*']
    ): Model {
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
     * @return Model
     * @throws NotFoundException
     */
    public static function findOrFail(int | string $id, $select = ['*']): Model
    {
        $result = static::find($id, $select);

        if (is_null($result)) {
            throw new NotFoundException('No recordings found at ' . $id . '.');
        }

        return $result;
    }

    /**
     * Create a persist information
     *
     * @param array $data
     * @return Model
     */
    public static function create(array $data): Model
    {
        $model = new static;

        if ($model->timestamps) {
            $data = array_merge($data, [
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
        }

        // Check if the primary key existe on updating data
        if (!array_key_exists($model->primary_key, $data)) {
            if ($model->auto_increment) {
                $id_value = [$model->primary_key => null];

                $data = array_merge($id_value, $data);
            } elseif ($model->primary_key_type == 'string') {
                $data = array_merge([
                    $model->primary_key => ''
                ], $data);
            }
        }

        // Override the olds model attributes
        $model->setAttributes($data);

        if ($model->save() == 1) {
            // Throw the onCreated event
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
     * @return array
     */
    public static function paginate(int $page_number, int $current = 0, ?int $chunk = null): array
    {
        return static::query()->paginate($page_number, $current, $chunk);
    }

    /**
     * Allows to associate listener
     *
     * @param callable $cb
     * @throws
     */
    public static function deleted(callable $cb): void
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
    public static function created(callable $cb): void
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
    public static function updated(callable $cb): void
    {
        $env = static::formatEventName('onupdated');

        listen_event_once($env, $cb);
    }

    /**
     * Initialize the connection
     *
     * @return Builder
     * @throws
     */
    public static function query(): Builder
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
            $table = Str::snake(end($parts)).'s';
        } else {
            $table = $properties['table'];
        }

        // Check the connection parameter before apply
        if (isset($properties['connection']) && !is_null($properties['connection'])) {
            DB::connection($properties['connection']);
        }

        // Check the prefix parameter before apply
        if (isset($properties['prefix']) && !is_null($properties['prefix'])) {
            $prefix = $properties['prefix'];
        } else {
            $prefix = DB::getAdapterConnection()->getTablePrefix();
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
    public function getKeyValue(): mixed
    {
        return $this->original[$this->primary_key] ?? null;
    }

    /**
     * Retrieves the primary key
     *
     * @return string
     */
    public function getKey(): string
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

        $updated = $model->where($this->primary_key, $primary_key_value)->update($update_data);

        // Fire the updated event if there are affected row
        if ($updated) {
            $this->fireEvent('onupdated');
        }

        return $updated;
    }

    /**
     * Transtype the primary key value
     *
     * @param mixed $primary_key_value
     * @return mixed
     */
    private function transtypeKeyValue(mixed $primary_key_value): mixed
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
     * @param array $attributes
     * @return int
     * @throws
     */
    public function update(array $attributes): int
    {
        $primary_key_value = $this->getKeyValue();

        if ($primary_key_value == null) {
            return 0;
        }

        $model = static::query();

        // We set the primary key value
        $this->original[$this->primary_key] = $primary_key_value;

        $data_for_updating = $attributes;

        if (count($this->original) > 0) {
            $data_for_updating = [];
            foreach ($attributes as $key => $value) {
                if (array_key_exists($key, $this->original) || $this->original[$key] !== $value) {
                    $data_for_updating[$key] = $value;
                }
            }
        }

        // When the data for updating list is empty, we load the original data
        if (count($data_for_updating) == 0) {
            $this->fireEvent('onupdated');
            return true;
        }

        // We update the model data right now
        $updated = $model->where($this->primary_key, $primary_key_value)->update($data_for_updating);

        // Fire the updated event if there are affected row
        if ($updated) {
            $this->fireEvent('onupdated');
        }

        return $updated;
    }

    /**
     * Delete a record
     *
     * @return int
     * @throws
     */
    public function delete(): int
    {
        $primary_key_value = $this->getKeyValue();

        $model = static::query();

        if ($primary_key_value == null) {
            return 0;
        }

        if (!$model->exists($this->primary_key, $primary_key_value)) {
            return 0;
        }

        // We apply the delete action
        $deleted = $model->where($this->primary_key, $primary_key_value)->delete();

        // Fire the deleted event if there are affected row
        if ($deleted) {
            $this->fireEvent('ondeleted');
        }

        return $deleted;
    }

    /**
     * Delete Active Record by column name
     *
     * @param string $column
     * @param string|int $value
     * @return int
     */
    public static function deleteBy($column, $value): int
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
    public function touch(): bool
    {
        if ($this->timestamps) {
            $this->setAttribute($this->updated_at, date('Y-m-d H:i:s'));
        }

        return (bool) $this->save();
    }

    /**
     * Assign values to class attributes
     *
     * @param array $attributes
     */
    public function setAttributes(array $attributes): void
    {
        $this->attributes = $attributes;
    }

    /**
     * Assign a value
     *
     * @param string $key
     * @param string $value
     */
    public function setAttribute(string $key, string $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Set connection point
     *
     * @param string $connection
     * @return Builder
     */
    public function setConnection(string $connection): Builder
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
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * Allows you to recover an attribute
     *
     * @param  string $key
     * @return mixed|null
     */
    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Lists of mutable properties
     *
     * @return array
     */
    private function mutableDateAttributes(): array
    {
        return array_merge($this->dates, [
            $this->created_at, $this->updated_at, 'expired_at', 'logged_at', 'signed_at'
        ]);
    }

    /**
     * Get the table name.
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Returns the data
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->attributes;
    }

    /**
     * Returns the data
     *
     * @return string
     */
    public function toJson(): string
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
    public function jsonSerialize(): array
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
     * @return mixed
     */
    public function __get(string $name): mixed
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
    public function __toString(): string
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
