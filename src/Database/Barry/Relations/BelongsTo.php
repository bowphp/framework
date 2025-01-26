<?php

declare(strict_types=1);

namespace Bow\Database\Barry\Relations;

use Bow\Cache\Cache;
use Bow\Database\Barry\Model;
use Bow\Database\Barry\Relation;
use Bow\Database\Exception\QueryBuilderException;

class BelongsTo extends Relation
{
    /**
     * Create a new belongs to relationship instance.
     *
     * @param Model $related
     * @param Model $parent
     * @param string $foreign_key
     * @param string $local_key
     */
    public function __construct(
        Model $related,
        Model $parent,
        string $foreign_key,
        string $local_key
    ) {
        $this->local_key = $local_key;
        $this->foreign_key = $foreign_key;

        parent::__construct($related, $parent);
    }

    /**
     * Get the results of the relationship.
     *
     * @return mixed
     */
    public function getResults(): mixed
    {
        $key = $this->query->getTable() . ":belongsto:" . $this->related->getTable() . ":" . $this->foreign_key;

        $cache = Cache::store('file')->get($key);

        if (!is_null($cache)) {
            $related = new $this->related();
            $related->setAttributes($cache);
            return $related;
        }

        $result = $this->query->first();

        if (!is_null($result)) {
            Cache::store('file')->add($key, $result->toArray(), 500);
        }

        return $result;
    }

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     * @throws QueryBuilderException
     */
    public function addConstraints(): void
    {
        if (!static::$has_constraints) {
            return;
        }

        // For belongs to relationships, which are essentially the inverse of has one
        // or has many relationships, we need to actually query on the primary key
        // of the related models matching on the foreign key that's on a parent.
        $foreign_key_value = $this->parent->getAttribute($this->foreign_key);
        $this->query->where($this->local_key, '=', $foreign_key_value);
    }
}
