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
        if (is_callable($data)) {
            $content = $data();
        } else {
            $content = $data;
        }

        return $this->query->insert(['key' => $key, "data" => serialize($content), "expire" => $time]);
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
        $result = $this->query->where("key", $key)->first();

        $value = unserialize($result->data);

        return false;
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null): mixed
    {
        if (!$this->has($key)) {
            return is_callable($default) ? $default() : $default;
        }

        $result = $this->query->where("key", $key)->first();

        $value = unserialize($result->data);

        return is_null($value) ? $default : $value;
    }

    /**
     * @inheritDoc
     */
    public function addTime(string $key, int $time): bool
    {
        $result = $this->query->where("key", $key)->first();

        $result->expire += $time;

        return $$this->query->where("key", $key)->update((array) $result);
    }

    /**
     * @inheritDoc
     */
    public function timeOf(string $key): int|bool|string
    {
        $result = $this->query->where("key", $key)->first();

        return $result->expire;
    }

    /**
     * @inheritDoc
     */
    public function forget(string $key): bool
    {
        return $this->query->where("key", $key)->delete();
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        return $this->query->where("key", $key)->exists();
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
