<?php

declare(strict_types=1);

namespace Bow\Database\Barry\Relations;

use Bow\Database\Barry\Model;
use Bow\Database\Barry\Relation;

class HasOne extends Relation
{
    /**
     * Create a new belongs to relationship instance.
     *
     * @param Model $related
     * @param Model $parent
     * @param string  $foreign_key
     * @param string  $primary_key
     */
    public function __construct(Model $related, Model $parent, string $foreign_key, string $primary_key)
    {
        $this->primary_key = $primary_key;
        $this->foreign_key = $foreign_key;

        parent::__construct($related, $parent);
    }

    /**
     * Get the results of the relationship.
     *
     * @return Model
     */
    public function getResults(): ?Model
    {
        // The result is lazy-loaded once per parent model instance and kept in
        // memory by the model itself, so a plain query is enough here.
        return $this->query->first();
    }

    /**
     * Set the base constraints on the relation query.
     *
     * @return void
     */
    public function addConstraints(): void
    {
        if (!static::$has_constraints) {
            return;
        }

        $this->query = $this->query->where($this->foreign_key, $this->parent->getAttribute($this->primary_key));
    }

    /**
     * @inheritDoc
     */
    protected function eagerParentKey(): string
    {
        return $this->primary_key;
    }

    /**
     * @inheritDoc
     */
    protected function eagerRelatedKey(): string
    {
        return $this->foreign_key;
    }

    /**
     * @inheritDoc
     */
    protected function eagerIsMany(): bool
    {
        return false;
    }
}
