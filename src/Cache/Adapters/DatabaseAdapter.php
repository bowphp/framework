<?php

namespace Bow\Cache\Adapters;

use Bow\Cache\CacheException;
use Bow\Database\Database;
use Bow\Database\Exception\ConnectionException;
use Bow\Database\Exception\QueryBuilderException;
use Bow\Database\QueryBuilder;
use Exception;

class DatabaseAdapter implements CacheAdapterInterface
{
    /**
     * Define the QueryBuilder instance
     *
     * @var QueryBuilder
     */
    private QueryBuilder $query;

    /**
     * RedisAdapter constructor.
     *
     * @param  array $config
     * @return void
     * @throws ConnectionException
     */
    public function __construct(array $config)
    {
        $this->query = Database::connection($config["connection"])->table($config["table"]);
    }

    /**
     * @inheritDoc
     * @throws     Exception
     */
    public function set(string $key, mixed $data, ?int $time = null): bool
    {
        return $this->add($key, $data, $time);
    }

    /**
     * @inheritDoc
     * @throws     Exception
     */
    protected function add(string $key_name, mixed $data, ?int $time = null): bool
    {
        if ($this->has($key_name)) {
            return $this->update($key_name, $data, $time);
        }

        if (is_callable($data)) {
            $content = $data();
        } else {
            $content = $data;
        }

        $current_time = time();

        if (!is_null($time)) {
            $time += $current_time;
        } else {
            $time = $current_time;
        }

        return $this->query->insert(['key_name' => $key_name, "data" => serialize($content), "expire" => date("Y-m-d H:i:s", $time)]);
    }

    /**
     * @inheritDoc
     * @throws     QueryBuilderException
     */
    public function has(string $key_name): bool
    {
        return $this->query->where("key_name", $key_name)->exists();
    }

    /**
     * Update value from key
     *
     * @throws CacheException
     */
    private function update(string $key, mixed $data, ?int $time = null): mixed
    {
        if (is_callable($data)) {
            $content = $data();
        } else {
            $content = $data;
        }

        $result = $this->query->where("key_name", $key)->first();
        $result->data = serialize($content);

        if (!is_null($time)) {
            $result->expire = date("Y-m-d H:i:s", strtotime($result->expire) + $time);
        }

        return $this->query->where("key_name", $key)->update((array) $result);
    }

    /**
     * @inheritDoc
     * @throws     Exception
     */
    public function setMany(array $data): bool
    {
        $return = true;

        foreach ($data as $key => $value) {
            $return = $this->set($key, $value);
        }

        return $return;
    }

    /**
     * @inheritDoc
     * @throws     Exception
     */
    public function forever(string $key, mixed $data): bool
    {
        return $this->add($key, $data, -1);
    }

    /**
     * @inheritDoc
     * @throws     Exception
     */
    public function remember(string $key, int $time, callable $callback): mixed
    {
        if ($this->has($key)) {
            return $this->get($key);
        }

        $value = $callback();

        $this->set($key, $value, $time);

        return $value;
    }

    /**
     * @inheritDoc
     * @throws     Exception
     */
    public function increment(string $key, int $value = 1): int
    {
        $current = (int) $this->get($key, 0);
        $new = $current + $value;

        $this->set($key, $new);

        return $new;
    }

    /**
     * @inheritDoc
     * @throws     Exception
     */
    public function decrement(string $key, int $value = 1): int
    {
        $current = (int) $this->get($key, 0);

        $new = $current - $value;

        $this->set($key, $new);

        return $new;
    }

    /**
     * @inheritDoc
     * @throws     Exception
     */
    public function push(string $key, array $data): bool
    {
        if (!$this->has($key)) {
            throw new Exception("The key $key is not found");
        }

        $result = $this->query->where("key_name", $key)->first();

        $value = (array) unserialize($result->data);
        $result->data = serialize(array_merge($value, $data));

        return (bool) $this->query->where("key_name", $key)->update((array) $result);
    }

    /**
     * @inheritDoc
     * @throws     QueryBuilderException
     * @throws     Exception
     */
    public function setTime(string $key, int $time): bool
    {
        if (!$this->has($key)) {
            throw new Exception("The key $key is not found");
        }

        $result = $this->query->where("key_name", $key)->first();

        $result->expire = date("Y-m-d H:i:s", strtotime($result->expire) + $time);

        return (bool) $this->query->where("key_name", $key)->update((array) $result);
    }

    /**
     * @inheritDoc
     * @throws     QueryBuilderException
     * @throws     Exception
     */
    public function timeOf(string $key): int|bool|string
    {
        if (!$this->has($key)) {
            return false;
        }

        $result = $this->query->where("key_name", $key)->first();

        $current_time = time();

        return strtotime($result->expire, $current_time) - $current_time;
    }

    /**
     * @inheritDoc
     * @throws     QueryBuilderException
     * @throws     Exception
     */
    public function forget(string $key_name): bool
    {
        return $this->query->where("key_name", $key_name)->delete();
    }

    /**
     * @inheritDoc
     * @throws     QueryBuilderException
     */
    public function expired(string $key): bool
    {
        return $this->get($key);
    }

    /**
     * @inheritDoc
     * @throws     QueryBuilderException
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->has($key)) {
            return is_callable($default) ? $default() : $default;
        }

        $result = $this->query->where("key_name", $key)->first();

        if (strtotime($result->expire) < time()) {
            $this->forget($key);
            return is_callable($default) ? $default() : $default;
        }

        $value = unserialize($result->data);

        return is_null($value) ? $default : $value;
    }

    /**
     * @inheritDoc
     */
    public function clear(): void
    {
        $this->query->truncate();
    }
}
