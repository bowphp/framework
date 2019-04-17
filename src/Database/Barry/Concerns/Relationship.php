<?php

namespace Bow\Database\Barry\Concerns;

use Bow\Database\Barry\Relations\BelongsTo;
use Bow\Database\Barry\Relations\HasMany;
use Bow\Database\Barry\Relations\HasOne;
use Bow\Database\Barry\Relations\BelongsToMany;

trait Relationship
{
    /**
     * Get the table key
     *
     * @return string
     */
    abstract public function getKey(): string;

    /**
     * The has one relative
     *
     * @param strinf $related
     * @param string $foreign_key
     * @param string $local_key
     * @return BelongsTo
     */
    public function belongsTo(string $related, ?string $foreign_key = null, ?string $local_key = null)
    {
        $relatedModel = app()->make($related);
        $local_key = $local_key ?? $this->getKey();
        // Make the table name singular and append ID to it
        // FIXME: Use a more reliable approach
        $foreign_key = $foreign_key ?? rtrim($relatedModel->getTable(), 's').'_id';
        return new BelongsTo($relatedModel, $this, $foreign_key, $local_key);
    }

    /**
     * The belongs to many relative
     *
     * @param strinf $related
     * @param string $primary_key
     * @param string $foreign_key
     * @return BelongsToMany
     */
    public function belongsToMany(string $related, ?string $primary_key = null, ?string $foreign_key = null)
    {
        $relatedModel = app()->make($related);
        $local_key = $local_key ?? $this->getKey();
        // Make the table name singular and append ID to it
        // FIXME: Use a more reliable approach
        $foreign_key = $foreign_key ?? rtrim($relatedModel->getTable(), 's').'_id';
        return new BelongsToMany($relatedModel, $this, $primary_key, $foreign_key);
    }

    /**
     * The has many relative
     *
     * @param strinf $related
     * @param string $primary_key
     * @param string $foreign_key
     * @return HasMany
     */
    public function hasMany(string $related, string $primary_key = '', string $foreign_key = '')
    {
        $relatedModel = app()->make($related);
        $local_key = $local_key ?? $this->getKey();
        // Make the table name singular and append ID to it
        // FIXME: Use a more reliable approach
        $foreign_key = $foreign_key ?? rtrim($relatedModel->getTable(), 's').'_id';
        return new HasMany($relatedModel, $this, $primary_key, $foreign_key);
    }

    /**
     * The has one relative
     *
     * @param strinf $related
     * @param string $primary_key
     * @param string $foreign_key
     * @return HasOne
     */
    public function hasOne(string $related, string $primary_key = '', string $foreign_key = '')
    {
        $relatedModel = app()->make($related);
        $local_key = $local_key ?? $this->getKey();
        // Make the table name singular and append ID to it
        // FIXME: Use a more reliable approach
        $foreign_key = $foreign_key ?? rtrim($relatedModel->getTable(), 's').'_id';
        return new HasOne($relatedModel, $this, $primary_key, $foreign_key);
    }
}
