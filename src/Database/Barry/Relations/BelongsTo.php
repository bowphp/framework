<?php

namespace Bow\Database\Barry\Relations;

use Bow\Database\Barry\Relation;
use Bow\Database\Barry\Model;
use Bow\Database\Collection;

class BelongsTo extends Relation
{
    /**
     * The foreign key of the parent model.
     *
     * @var string
     */
    protected $foreign_key;

    /**
     * The associated key on the parent model.
     *
     * @var string
     */
    protected $local_key;

    /**
     * Create a new belongs to relationship instance.
     *
     * @param Model $related
     * @param Model $parent
     * @param string  $foreign_key
     * @param string  $local_key
     */
    public function __construct(Model $related, Model $parent, $foreign_key, $local_key)
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
    public function getResults()
    {
        // TODO: Cache the result
        return $this->query->first();
    }

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
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
