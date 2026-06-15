<?php

namespace Bow\Database;

use Bow\Event\Contracts\AppEvent;
use Bow\Event\Dispatchable;
use Bow\Support\Serializes;

final class QueryEvent implements AppEvent
{
    use Dispatchable;
    use Serializes;

    /**
     * The query data
     *
     * @var mixed
     */
    public string $sql;

    /**
     * Define the query execution time
     *
     * @var mixed
     */
    public float $execution_time;

    /**
     * The query bindings
     *
     * @var array
     */
    public array $bindings;

    /**
     * QueryEvent constructor.
     *
     * @param string $sql
     * @param float $execution_time
     * @param array $bindings
     */
    public function __construct(string $sql, float $execution_time = 0, array $bindings = [])
    {
        $this->sql = $sql;
        $this->execution_time = $execution_time;
        $this->bindings = $bindings;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return QueryEvent::class;
    }

    /**
     * Prevent setting properties dynamically
     *
     * @param  string $name
     * @param  mixed  $value
     * @throws \Exception
     */
    final public function __set($name, $value)
    {
        throw new \Exception("Cannot set property $name on QueryEvent");
    }
}
