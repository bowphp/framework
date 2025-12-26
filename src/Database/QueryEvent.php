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
     * The query bindings
     *
     * @var array
     */
    public array $bindings;

    /**
     * QueryEvent constructor.
     *
     * @param array $data
     */
    public function __construct(string $sql, array $bindings = [])
    {
        $this->sql = $sql;
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
    public function __set($name, $value)
    {
        throw new \Exception("Cannot set property $name on QueryEvent");
    }
}
