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
     * @param string   $local_key
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
     */
    public function addConstraints(): void
    {
        // Match the related foreign key column against the parent's primary key.
        // local_key holds the foreign key column name; foreign_key holds the
        // parent primary key name, so filtering must use local_key here.
        $this->query = $this->query->where($this->local_key, $this->parent->getKeyValue());
    }

    /**
     * @inheritDoc
     */
    protected function eagerParentKey(): string
    {
        return $this->parent->getKey();
    }

    /**
     * @inheritDoc
     */
    protected function eagerRelatedKey(): string
    {
        return $this->local_key;
    }

    /**
     * @inheritDoc
     */
    protected function eagerIsMany(): bool
    {
        return true;
    }
}
