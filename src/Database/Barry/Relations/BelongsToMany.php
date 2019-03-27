<?php

namespace Bow\Database\Barry\Relations;

use Bow\Database\Barry\Relation;
use Bow\Database\Barry\Model;

/**
* @author Salomon Dion (dev.mrdion@gmail.com)
*/
class BelongsToMany extends Relation
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
     * @param string   $foreignKey
     * @param string   $otherKey
     * @param string   $relation
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
        return $this->query->get();
    }

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints()
    {
        if (static::$hasConstraints) {
        }
    }
}
