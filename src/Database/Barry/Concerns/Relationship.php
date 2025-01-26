<?php

declare(strict_types=1);

namespace Bow\Database\Barry\Concerns;

use Bow\Database\Barry\Relations\BelongsTo;
use Bow\Database\Barry\Relations\BelongsToMany;
use Bow\Database\Barry\Relations\HasMany;
use Bow\Database\Barry\Relations\HasOne;

trait Relationship
{
    /**
     * The has one relative
     *
     * @param string $related
     * @param string|null $foreign_key
     * @param string|null $local_key
     * @return BelongsTo
     */
    public function belongsTo(
        string  $related,
        ?string $foreign_key = null,
        ?string $local_key = null
    ): BelongsTo
    {
        // Create the new instance of model from container
        $related_model = app()->make($related);

        if (is_null($local_key)) {
            $local_key = $this->getKey();
        }

        // We build here the foreign key name
        if (is_null($foreign_key)) {
            $foreign_key = rtrim($related_model->getTable(), 's') . '_id';
        }

        return new BelongsTo($related_model, $this, $foreign_key, $local_key);
    }

    /**
     * Get the table key
     *
     * @return string
     */
    abstract public function getKey(): string;

    /**
     * The belongs to many relative
     *
     * @param string $related
     * @param string|null $primary_key
     * @param string|null $foreign_key
     * @return BelongsToMany
     */
    public function belongsToMany(
        string  $related,
        ?string $primary_key = null,
        ?string $foreign_key = null
    ): BelongsToMany
    {
        $related_model = app()->make($related);

        if (is_null($primary_key)) {
            $primary_key = $this->getKey();
        }

        // We build the foreign key name
        if (is_null($foreign_key)) {
            $foreign_key = rtrim($related_model->getTable(), 's') . '_id';
        }

        return new BelongsToMany($related_model, $this, $primary_key, $foreign_key);
    }

    /**
     * The has many relative
     *
     * @param string $related
     * @param string|null $primary_key
     * @param string|null $foreign_key
     * @return HasMany
     */
    public function hasMany(
        string  $related,
        ?string $primary_key = null,
        ?string $foreign_key = null
    ): HasMany
    {
        $related_model = app()->make($related);

        if (is_null($primary_key)) {
            $primary_key = $this->getKey();
        }

        // We build the foreign key name
        if (is_null($foreign_key)) {
            $foreign_key = rtrim($related_model->getTable(), 's') . '_id';
        }

        return new HasMany($related_model, $this, $primary_key, $foreign_key);
    }

    /**
     * The has one relative
     *
     * @param string $related
     * @param string|null $foreign_key
     * @param string|null $primary_key
     * @return HasOne
     */
    public function hasOne(
        string  $related,
        ?string $foreign_key = null,
        ?string $primary_key = null
    ): HasOne
    {
        $related_model = app()->make($related);

        if (is_null($primary_key)) {
            $primary_key = $this->getKey();
        }

        // We build the foreign key name
        if (is_null($foreign_key)) {
            $foreign_key = rtrim($related_model->getTable(), 's') . '_id';
        }

        return new HasOne($related_model, $this, $foreign_key, $primary_key);
    }
}
