<?php

declare(strict_types=1);

namespace Bow\Database\Barry\Relations;

use Bow\Database\Barry\Model;
use Bow\Database\Barry\Relation;
use Bow\Database\Collection;
use Bow\Database\Exception\QueryBuilderException;

class HasMany extends Relation
{
    /**
     * Create a new belongs to relationship instance.
     *
     * @param Model $related
     * @param Model $parent
     * @param string $foreign_key
     * @param string $local_key
     * @throws QueryBuilderException
     */
    public function __construct(Model $related, Model $parent, string $foreign_key, string $local_key)
    {
        parent::__construct($related, $parent);

        $this->local_key = $local_key;
        $this->foreign_key = $foreign_key;

        $this->query = $this->query->where($this->foreign_key, $this->parent->getKeyValue());
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
        //
    }
}
