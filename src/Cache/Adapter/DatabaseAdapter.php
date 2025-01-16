<?php

namespace Bow\Cache\Adapter;

use Bow\Database\Database;
use Bow\Database\Exception\ConnectionException;
use Bow\Database\Exception\QueryBuilderException;
use Bow\Database\QueryBuilder;
use Bow\Cache\Adapter\CacheAdapterInterface;

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
     * @param array $config
     * @return void
     * @throws ConnectionException
     */
    public function __construct(array $config)
    {
        $this->query = Database::connection($config["connection"])->table($config["table"]);
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function add(string $key, mixed $data, ?int $time = null): bool
    {
        if ($this->has($key)) {
            return $this->update($key, $data, $time);
        }

        if (is_callable($data)) {
            $content = $data();
        } else {
            $content = $data;
        }

        if (!is_null($time)) {
            $time += time();
        } else {
            $time = time();
        }

        $time = date("Y-m-d H:i:s");

        return $this->query->insert(['keyname' => $key, "data" => serialize($content), "expire" => $time]);
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function set(string $key, mixed $data, ?int $time = null): bool
    {
        return $this->add($key, $data, $time);
    }

    /**
     * @inheritDoc
     * @throws QueryBuilderException
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->has($key)) {
            return is_callable($default) ? $default() : $default;
        }

        $result = $this->query->where("keyname", $key)->first();

        $value = unserialize($result->data);

        return is_null($value) ? $default : $value;
    }

    /**
     * Update value from key
     * @throws \Exception
     */
    public function update(string $key, mixed $data, ?int $time = null): mixed
    {
        if (!$this->has($key)) {
            throw new \Exception("The key $key is not found");
        }

        if (is_callable($data)) {
            $content = $data();
        } else {
            $content = $data;
        }

        $result = $this->query->where("keyname", $key)->first();
        $result->data = serialize($content);

        if (!is_null($time)) {
            $result->expire = date("Y-m-d H:i:s", strtotime($result->expire) + $time);
        }

        return $this->query->where("keyname", $key)->update((array) $result);
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function addMany(array $data): bool
    {
        $return = true;

        foreach ($data as $attribute => $value) {
            $return = $this->add($attribute, $value);
        }

        return $return;
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function forever(string $key, mixed $data): bool
    {
        return $this->add($key, $data, -1);
    }

    /**
     * @inheritDoc
     * @throws \Exception
     */
    public function push(string $key, array $data): bool
    {
        if (!$this->has($key)) {
            throw new \Exception("The key $key is not found");
        }

        $result = $this->query->where("keyname", $key)->first();

        $value = (array) unserialize($result->data);
        $result->data = serialize(array_merge($value, $data));

        return (bool) $this->query->where("keyname", $key)->update((array) $result);
    }

    /**
     * @inheritDoc
     * @throws QueryBuilderException
     * @throws \Exception
     */
    public function addTime(string $key, int $time): bool
    {
        if (!$this->has($key)) {
            throw new \Exception("The key $key is not found");
        }

        $result = $this->query->where("keyname", $key)->first();

        $result->expire = date("Y-m-d H:i:s", strtotime($result->expire) + $time);

        return (bool) $this->query->where("keyname", $key)->update((array) $result);
    }

    /**
     * @inheritDoc
     * @throws QueryBuilderException
     * @throws \Exception
     */
    public function timeOf(string $key): int|bool|string
    {
        if (!$this->has($key)) {
            throw new \Exception("The key $key is not found");
        }

        $result = $this->query->where("keyname", $key)->first();

        return $result->expire;
    }

    /**
     * @inheritDoc
     * @throws QueryBuilderException
     * @throws \Exception
     */
    public function forget(string $key): bool
    {
        if (!$this->has($key)) {
            throw new \Exception("The key $key is not found");
        }

        return $this->query->where("keyname", $key)->delete();
    }

    /**
     * @inheritDoc
     * @throws QueryBuilderException
     */
    public function has(string $key): bool
    {
        return $this->query->where("keyname", $key)->exists();
    }

    /**
     * @inheritDoc
     * @throws QueryBuilderException
     */
    public function expired(string $key): bool
    {
        return $this->get($key);
    }

    /**
     * @inheritDoc
     */
    public function clear(): void
    {
        $this->query->truncate();
    }
}
