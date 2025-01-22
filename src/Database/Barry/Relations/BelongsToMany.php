<?php

declare(strict_types=1);

namespace Bow\Database\Barry\Relations;

use Bow\Database\Barry\Model;
use Bow\Database\Barry\Relation;
use Bow\Database\Collection;
use Bow\Database\Exception\QueryBuilderException;

class BelongsToMany extends Relation
{
    /**
     * Create a new belongs to relationship instance.
     *
     * @param Model $related
     * @param Model $parent
     * @param string $foreign_key
     * @param string $local_key
     */
    public function __construct(Model $related, Model $parent, string $foreign_key, string $local_key)
    {
        $this->local_key = $local_key;
        $this->foreign_key = $foreign_key;

        parent::__construct($related, $parent);
    }

    /**
     * Get the results of the relationship.
     *
     * @return Collection
     */
    public function getResults(): Collection
    {
        return $this->query->get();
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
