<?php

namespace Bow\Cache\Adapter;

use Bow\Database\Database;
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
     * @return mixed
     */
    public function __construct(array $config)
    {
        $this->query = Database::connection($config["connection"])->table($config["table"]);
    }

    /**
     * @inheritDoc
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
     */
    public function set(string $key, mixed $data, ?int $time = null): bool
    {
        return $this->add($key, $data, $time);
    }

    /**
     * @inheritDoc
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
     * @inheritDoc
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
     */
    public function forever(string $key, mixed $data): bool
    {
        return $this->add($key, $data, -1);
    }

    /**
     * @inheritDoc
     */
    public function push(string $key, array $data): bool
    {
        if (!$this->has($key)) {
            throw new \Exception("The key $key is not found");
        }

        $result = $this->query->where("keyname", $key)->first();

        $value = (array) unserialize($result->data);
        $result->data = serialize(array_merge($value, $data));

        return $$this->query->where("keyname", $key)->update((array) $result);
    }

    /**
     * @inheritDoc
     */
    public function addTime(string $key, int $time): bool
    {
        if (!$this->has($key)) {
            throw new \Exception("The key $key is not found");
        }

        $result = $this->query->where("keyname", $key)->first();

        $result->expire = date("Y-m-d H:i:s", strtotime($result->expire) + $time);

        return $$this->query->where("keyname", $key)->update((array) $result);
    }

    /**
     * @inheritDoc
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
     */
    public function has(string $key): bool
    {
        return $this->query->where("keyname", $key)->exists();
    }

    /**
     * @inheritDoc
     */
    public function expired(string $key): bool
    {
        $data = $this->get($key);

        return $data;
    }

    /**
     * @inheritDoc
     */
    public function clear(): void
    {
        $this->query->truncate();
    }
}
