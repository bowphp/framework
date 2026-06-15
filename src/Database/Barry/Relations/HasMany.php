<?php

declare(strict_types=1);

namespace Bow\Database\Barry\Relations;

use Bow\Database\Collection;
use Bow\Database\Barry\Model;
use Bow\Database\Barry\Relation;

class HasMany extends Relation
{
    /**
     * Create a new belongs to relationship instance.
     *
     * @param Model $related
     * @param Model $parent
     * @param string   $foreign_key
     * @param string   $primary_key
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
     */
    public function addConstraints(): void
    {
        if (!static::$has_constraints) {
            return;
        }

        // Match the related foreign key column against the parent's local key.
        // foreign_key is the column on the related table; primary_key is the
        // referenced column on the parent (its primary key by default).
        $this->query = $this->query->where(
            $this->foreign_key,
            $this->parent->getAttribute($this->primary_key)
        );
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
        return true;
    }
}
