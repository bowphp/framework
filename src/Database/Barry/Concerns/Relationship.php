<?php

namespace Bow\Database\Barry\Concerns;

use Bow\Database\Barry\Relations\BelongsTo;
use Bow\Database\Barry\Relations\HasMany;
use Bow\Database\Barry\Relations\HasOne;
use Bow\Database\Barry\Relations\BelongsToMany;

trait Relationship
{
    abstract public function getKey(): string;

    public function belongsTo(string $related, ?string $foreignKey = null, ?string $localKey = null)
    {
        $relatedModel = app()->make($related);
        $localKey = $localKey ?? $this->getKey();
        // Make the table name singular and append ID to it
        // FIXME: Use a more reliable approach
        $foreignKey = $foreignKey ?? rtrim($relatedModel->getTable(), 's').'_id';
        return new BelongsTo($relatedModel, $this, $foreignKey, $localKey);
    }

    public function belongsToMany(string $related, ?string $primaryKey = null, ?string $foreignKey = null)
    {
        $relatedModel = app()->make($related);
        $localKey = $localKey ?? $this->getKey();
        // Make the table name singular and append ID to it
        // FIXME: Use a more reliable approach
        $foreignKey = $foreignKey ?? rtrim($relatedModel->getTable(), 's').'_id';
        return new BelongsToMany($relatedModel, $this, $primaryKey, $foreignKey);
    }

    public function hasMany(string $related, string $primaryKey = '', string $foreignKey = '')
    {
        $relatedModel = app()->make($related);
        $localKey = $localKey ?? $this->getKey();
        // Make the table name singular and append ID to it
        // FIXME: Use a more reliable approach
        $foreignKey = $foreignKey ?? rtrim($relatedModel->getTable(), 's').'_id';
        return new HasMany($relatedModel, $this, $primaryKey, $foreignKey);
    }

    public function hasOne(string $related, string $primaryKey = '', string $foreignKey = '')
    {
        $relatedModel = app()->make($related);
        $localKey = $localKey ?? $this->getKey();
        // Make the table name singular and append ID to it
        // FIXME: Use a more reliable approach
        $foreignKey = $foreignKey ?? rtrim($relatedModel->getTable(), 's').'_id';
        return new HasOne($relatedModel, $this, $primaryKey, $foreignKey);
    }
}
