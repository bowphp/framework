<?php

declare(strict_types=1);

namespace Bow\Database\Barry;

use ArrayAccess;
use BadMethodCallException;
use Bow\Database\Barry\Concerns\Relationship;
use Bow\Database\Barry\Traits\ArrayAccessTrait;
use Bow\Database\Barry\Traits\EventTrait;
use Bow\Database\Barry\Traits\SerializableTrait;
use Bow\Database\Collection;
use Bow\Database\Database as DB;
use Bow\Database\Exception\ConnectionException;
use Bow\Database\Exception\NotFoundException;
use Bow\Database\Exception\QueryBuilderException;
use Bow\Database\Pagination;
use Bow\Support\Str;
use Carbon\Carbon;
use JsonSerializable;
use ReflectionClass;

/**
 * @method select(array|string[] $select)
 * @method whereIn(string $primary_key, array $id)
 * @method get()
 * @method where(string $column, mixed $value)
 * @method orderBy(string $latest, string $string)
 */
abstract class Model implements ArrayAccess, JsonSerializable
{
    use Relationship;
    use EventTrait;
    use ArrayAccessTrait;
    use SerializableTrait;

    /**
     * The query builder instance
     *
     * @var ?Builder
     */
    protected static ?Builder $builder = null;
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
    protected string $prefix = '';
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
     * @var ?string
     */
    protected ?string $connection = null;
    /**
     * The table columns listing, initialize in first query
     *
     * @var array
     */
    private array $original = [];

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
     * Get the table name.
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Initialize the connection
     *
     * @return Builder
     * @throws
     */
    public static function query(): Builder
    {
        if (
            static::$builder instanceof Builder
            && static::$builder->getModel() == static::class
        ) {
            if (DB::getConnectionName() == static::$builder->getAdapterName()) {
                return static::$builder;
            }
        }

        // Reflection action
        $reflection = new ReflectionClass(static::class);

        $properties = $reflection->getDefaultProperties();

        if (!isset($properties['table']) || $properties['table'] == null) {
            $parts = explode('\\', static::class);
            $table = Str::lower(Str::snake(Str::plural(end($parts))));
        } else {
            $table = $properties['table'];
        }

        // Check the connection parameter before apply
        if (isset($properties['connection'])) {
            DB::connection($properties['connection']);
        }

        // Check the prefix parameter before apply
        $prefix = $properties['prefix'] ?? DB::getConnectionAdapter()->getTablePrefix();

        // Set the table prefix
        $table = $prefix . $table;

        static::$builder = new Builder($table, DB::getConnectionAdapter());
        static::$builder->setPrefix($prefix);
        static::$builder->setModel(static::class);

        return static::$builder;
    }

    /**
     * Set the connection
     *
     * @param string $name
     * @return Model
     * @throws ConnectionException
     */
    public static function connection(string $name): Model
    {
        $model = new static();

        $model->setConnection($name);

        return $model;
    }

    /**
     * Set connection point
     *
     * @param string $name
     * @return Builder
     * @throws ConnectionException
     */
    public function setConnection(string $name): Builder
    {
        $this->connection = $name;

        DB::connection($name);

        return static::query();
    }

    /**
     * Get all records
     *
     * @param array $columns
     *
     * @return Collection
     */
    public static function all(array $columns = []): Collection
    {
        $model = static::query();

        if (count($columns) > 0) {
            $model->select($columns);
        }

        return $model->get();
    }

    /**
     * Get latest
     *
     * @return Model|null
     */
    public static function latest(): ?Model
    {
        $query = new static();

        return $query->orderBy($query->latest, 'desc')->first();
    }

    /**
     * Get first rows
     *
     * @return Model|null
     */
    public static function first(): ?Model
    {
        return static::query()->first();
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
        $model = new static();
        $model->where($column, $value);

        return $model->get();
    }

    /**
     * Find information and delete it
     *
     * @param mixed $id
     * @param array $select
     *
     * @return Collection|Model|null
     */
    public static function findAndDelete(
        int|string|array $id,
        array            $select = ['*']
    ): Collection|Model|null
    {
        $model = static::find($id, $select);

        if (is_null($model)) {
            return null;
        }

        if ($model instanceof Collection) {
            $model->dropAll();
            return $model;
        }

        $model->delete();

        return $model;
    }

    /**
     * find
     *
     * @param mixed $id
     * @param array $select
     * @return Collection|static|null
     */
    public static function find(
        int|string|array $id,
        array            $select = ['*']
    ): Collection|Model|null
    {
        $id = (array)$id;

        $model = new static();
        $model->select($select);
        $model->whereIn($model->primary_key, $id);

        if (count($id) != 1) {
            return $model->get();
        }

        return $model->first();
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

        // Fire the deleting event
        $this->fireEvent('model.deleting');

        // We apply the delete action
        $deleted = $model->where($this->primary_key, $primary_key_value)->delete();

        // Fire the deleted event if there are affected row
        if ($deleted) {
            $this->fireEvent('model.deleted');
        }

        return $deleted;
    }

    /**
     * Retrieves the primary key value
     *
     * @return mixed
     */
    public function getKeyValue(): mixed
    {
        return $this->original[$this->primary_key] ?? $this->attributes[$this->primary_key] ?? null;
    }

    /**
     * Find information by id or throws an
     * exception in data box not found
     *
     * @param mixed $id
     * @param array $select
     * @return Model
     * @throws NotFoundException
     */
    public static function findOrFail(int|string $id, array $select = ['*']): Model
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
        $model = new static();

        if ($model->timestamps) {
            $data = array_merge($data, [
                $model->created_at => date('Y-m-d H:i:s'),
                $model->updated_at => date('Y-m-d H:i:s')
            ]);
        }

        // Check if the primary key exist on updating data
        if (
            !array_key_exists($model->primary_key, $data)
            && static::$builder->getAdapterName() !== "pgsql"
        ) {
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
        $model->save();

        return $model;
    }

    /**
     * Save aliases on insert action
     *
     * @return int
     * @throws
     */
    public function save(): int
    {
        $builder = static::query();

        // Get the current primary key value
        $primary_key_value = $this->getKeyValue();

        // If primary key value is null, we are going to start the creation of new row
        if (is_null($primary_key_value)) {
            return $this->writeRows($builder);
        }

        $primary_key_value = $this->transtypeKeyValue($primary_key_value);

        // Check the existent in database
        if (!$builder->exists($this->primary_key, $primary_key_value)) {
            return $this->writeRows($builder);
        }

        // We set the primary key value
        $this->original[$this->primary_key] = $primary_key_value;

        $update_data = array_filter($this->attributes, function ($value, $key) {
            return !array_key_exists($key, $this->original) || $this->original[$key] !== $value;
        }, ARRAY_FILTER_USE_BOTH);

        // When the update data is empty, we load the original data
        if (count($update_data) == 0) {
            $update_data = $this->original;
        }

        // Fire the updating event
        $this->fireEvent('model.updating');

        // Execute update model
        $updated = $builder->where($this->primary_key, $primary_key_value)->update($update_data);

        // Fire the updated event if there are affected row
        if ($updated) {
            $this->fireEvent('model.updated');
        }

        return $updated;
    }

    /**
     * Create the new row
     *
     * @param Builder $builder
     * @return int
     */
    private function writeRows(Builder $builder): int
    {
        // Fire the creating event
        $this->fireEvent('model.creating');

        $primary_key_value = $this->getKeyValue();

        // Insert information in the database
        $row_affected = $builder->insert($this->attributes);

        // We get a last insertion id value
        if (static::$builder->getAdapterName() == 'pgsql') {
            if ($this->auto_increment) {
                $sequence = $this->table . "_" . $this->primary_key . '_seq';
                $primary_key_value = static::$builder->getPdo()->lastInsertId($sequence);
            }
        } else {
            $primary_key_value = static::$builder->getPdo()->lastInsertId();
        }

        if ((int)$primary_key_value == 0) {
            $primary_key_value = $this->attributes[$this->primary_key] ?? null;
        }

        $primary_key_value = !is_numeric($primary_key_value) ? $primary_key_value : (int)$primary_key_value;

        // Set the primary key value
        $this->attributes[$this->primary_key] = $primary_key_value;
        $this->original = $this->attributes;

        if ($row_affected == 1) {
            $this->fireEvent('model.created');
        }

        return $row_affected;
    }

    /**
     * Trans-type the primary key value
     *
     * @param mixed $primary_key_value
     * @return string|int|float
     */
    private function transtypeKeyValue(mixed $primary_key_value): string|int|float
    {
        // Transtype value to the define primary key type
        if ($this->primary_key_type == 'int') {
            return (int)$primary_key_value;
        }

        if ($this->primary_key_type == 'float' || $this->primary_key_type == 'double') {
            return (float)$primary_key_value;
        }

        return (string)$primary_key_value;
    }

    /**
     * Delete a record
     *
     * @param array $attributes
     * @return int|bool
     * @throws
     */
    public function update(array $attributes): int|bool
    {
        $primary_key_value = $this->getKeyValue();

        // return 0 if the primary key is not define for update
        if ($primary_key_value == null) {
            return false;
        }

        $model = static::query();

        // We set the primary key value
        $this->original[$this->primary_key] = $primary_key_value;

        $data_for_updating = $attributes;

        if (count($this->original) > 0) {
            $data_for_updating = array_filter($attributes, function ($value, $key) {
                return array_key_exists($key, $this->original) || $this->original[$key] !== $value;
            }, ARRAY_FILTER_USE_BOTH);
        }

        // Fire the updating event
        $this->fireEvent('model.updating');

        // When the data for updating list is empty, we load the original data
        if (count($data_for_updating) == 0) {
            $this->fireEvent('model.updated');
            return true;
        }

        // We update the model data right now
        $updated = $model->where($this->primary_key, $primary_key_value)->update($data_for_updating);

        // Fire the updated event if there are affected row
        if ($updated) {
            $this->fireEvent('model.updated');
        }

        return $updated;
    }

    /**
     * Pagination configuration
     *
     * @param int $page_number
     * @param int $current
     * @param int|null $chunk
     * @return Pagination
     */
    public static function paginate(int $page_number, int $current = 0, ?int $chunk = null): Pagination
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
        $env = static::formatEventName('model.deleted');

        event()->once($env, $cb);
    }

    /**
     * Allows to associate listener
     *
     * @param callable $cb
     * @throws
     */
    public static function deleting(callable $cb): void
    {
        $env = static::formatEventName('model.deleted');

        event()->once($env, $cb);
    }

    /**
     * Allows to associate a listener
     *
     * @param callable $cb
     * @throws
     */
    public static function creating(callable $cb): void
    {
        $env = static::formatEventName('model.creating');

        event()->once($env, $cb);
    }

    /**
     * Allows to associate a listener
     *
     * @param callable $cb
     * @throws
     */
    public static function created(callable $cb): void
    {
        $env = static::formatEventName('model.created');

        event()->once($env, $cb);
    }

    /**
     * Allows to associate a listener
     *
     * @param callable $cb
     * @throws
     */
    public static function updating(callable $cb): void
    {
        $env = static::formatEventName('model.updating');

        event()->once($env, $cb);
    }

    /**
     * Allows to associate a listener
     *
     * @param callable $cb
     * @throws
     */
    public static function updated(callable $cb): void
    {
        $env = static::formatEventName('model.updated');

        event()->once($env, $cb);
    }

    /**
     * Delete Active Record by column name
     *
     * @param string $column
     * @param mixed $value
     * @return int
     * @throws QueryBuilderException
     */
    public static function deleteBy(string $column, mixed $value): int
    {
        $model = static::query();

        return $model->where($column, $value)->delete();
    }

    /**
     * __callStatic
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public static function __callStatic(string $name, array $arguments)
    {
        $model = static::query();

        if (method_exists($model, $name)) {
            return call_user_func_array([$model, $name], $arguments);
        }

        throw new BadMethodCallException(
            'method ' . $name . ' is not defined.',
            E_ERROR
        );
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
     * Retrieves the primary key
     *
     * @return string
     */
    public function getKeyType(): string
    {
        return $this->primary_key_type;
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

        return (bool)$this->save();
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
     * Retrieves the list of attributes.
     *
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
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
     * Allows you to recover an attribute
     *
     * @param string $key
     * @return mixed|null
     */
    public function getAttribute(string $key): mixed
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Returns the data
     *
     * @return array
     */
    public function toArray(): array
    {
        return array_filter($this->attributes, function ($key) {
            return !in_array($key, $this->hidden);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return array_filter($this->attributes, function ($key) {
            return !in_array($key, $this->hidden);
        }, ARRAY_FILTER_USE_KEY);
    }

    /**
     * __get
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        $attribute_exists = isset($this->attributes[$name]);

        if (!$attribute_exists && method_exists($this, $name)) {
            $result = $this->$name();
            if ($result instanceof Relation) {
                return $result->getResults();
            }
            return $result;
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
                return (int)$value;
            }
            if ($type === "float") {
                return (float)$value;
            }
            if ($type === "double") {
                return (double)$value;
            }
            if ($type === "json") {
                if (is_array($value)) {
                    return (object)$value;
                }
                if (is_object($value)) {
                    return (object)$value;
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
                    return (array)$value;
                }
                if (is_object($value)) {
                    return (array)$value;
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
     * @param mixed $value
     */
    public function __set(string $name, mixed $value)
    {
        $this->attributes[$name] = $value;
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
     * __toString
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toJson();
    }

    /**
     * Returns the data
     *
     * @return string
     */
    public function toJson(): string
    {
        $data = array_filter($this->attributes, function ($key) {
            return !in_array($key, $this->hidden);
        }, ARRAY_FILTER_USE_KEY);

        return json_encode($data);
    }

    /**
     * __call
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments = [])
    {
        $model = static::query();

        if (method_exists($model, $name)) {
            return call_user_func_array([$model, $name], $arguments);
        }

        throw new BadMethodCallException(
            'method ' . $name . ' is not defined.',
            E_ERROR
        );
    }
}
