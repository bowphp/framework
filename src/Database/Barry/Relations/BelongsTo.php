<?php

namespace Bow\Database\Barry\Relations;

use Bow\Database\Barry\Relation;
use Bow\Database\Barry\Model;

class BelongsTo extends Relation
{
    /**
     * The foreign key of the parent model.
     *
     * @var string
     */
    protected $foreignKey;

    /**
     * The associated key on the parent model.
     *
     * @var string
     */
    protected $localKey;

    /**
     * Create a new belongs to relationship instance.
     *
     * @param Model $related
     * @param Model $parent
     * @param string    $foreignKey
     * @param string    $otherKey
     * @param string    $relation
     */
    public function __construct(Model $related, Model $parent, string $foreignKey, string $localKey)
    {
        $this->localKey = $localKey;
        $this->foreignKey = $foreignKey;
        parent::__construct($related, $parent);
    }

    /**
     * Get the results of the relationship.
     *
     * @param  $relation
     *
     * @return Model
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
        if (static::$hasConstraints) {
            // For belongs to relationships, which are essentially the inverse of has one
            // or has many relationships, we need to actually query on the primary key
            // of the related models matching on the foreign key that's on a parent.
            $foreignKeyValue = $this->parent->getAttribute($this->foreignKey);
            $this->query->where($this->localKey, '=', $foreignKeyValue);
        }
    }
}
