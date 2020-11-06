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
    abstract public function getKey();

    /**
     * The has one relative
     *
     * @param string $related
     * @param string $foreign_key
     * @param string $local_key
     * @return BelongsTo
     */
    public function belongsTo(string $related, string $foreign_key = null, string $local_key = null)
    {
        $related_model = app()->make($related);

        if (is_null($local_key)) {
            $local_key = $this->getKey();
        }

        if (is_null($foreign_key)) {
            $foreign_key = rtrim($related_model->getTable(), 's') . '_id';
        }

        return new BelongsTo($related_model, $this, $foreign_key, $local_key);
    }

    /**
     * The belongs to many relative
     *
     * @param string $related
     * @param string $primary_key
     * @param string $foreign_key
     * @return BelongsToMany
     */
    public function belongsToMany(string $related, string $primary_key = null, string $foreign_key = null)
    {
        $related_model = app()->make($related);

        if (strlen($foreign_key) == 0) {
            $foreign_key = $this->getKey();
        }

        if (is_null($foreign_key)) {
            $foreign_key = rtrim($related_model->getTable(), 's') . '_id';
        }

        return new BelongsToMany($related_model, $this, $primary_key, $foreign_key);
    }

    /**
     * The has many relative
     *
     * @param string $related
     * @param string $primary_key
     * @param string $foreign_key
     * @return HasMany
     */
    public function hasMany(string $related, string $primary_key = '', string $foreign_key = '')
    {
        $related_model = app()->make($related);

        if (strlen($foreign_key) == 0) {
            $foreign_key = $this->getKey();
        }

        if (is_null($foreign_key)) {
            $foreign_key = rtrim($related_model->getTable(), 's') . '_id';
        }

        return new HasMany($related_model, $this, $primary_key, $foreign_key);
    }

    /**
     * The has one relative
     *
     * @param string $related
     * @param string $primary_key
     * @param string $foreign_key
     * @return HasOne
     */
    public function hasOne(string $related, string $primary_key = '', string $foreign_key = '')
    {
        $related_model = app()->make($related);

        if (strlen($foreign_key) == 0) {
            $foreign_key = $this->getKey();
        }

        if (is_null($foreign_key)) {
            $foreign_key = rtrim($related_model->getTable(), 's') . '_id';
        }

        return new HasOne($related_model, $this, $primary_key, $foreign_key);
    }
}
