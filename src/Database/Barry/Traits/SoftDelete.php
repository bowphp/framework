<?php

declare(strict_types=1);

namespace Bow\Database\Barry\Traits;

use Bow\Database\Barry\Builder;

/**
 * Soft delete support for Barry models.
 *
 * Using this trait:
 *   - `$model->delete()` writes the current timestamp into the `deleted_at`
 *     column instead of physically removing the row.
 *   - `$model->restore()` clears `deleted_at`.
 *   - `$model->forceDelete()` performs a real DELETE.
 *   - Use the static query helpers `withTrashed()`, `withoutTrashed()`, and
 *     `onlyTrashed()` to scope your queries.
 *
 * Schema requirement: the table must carry a nullable `deleted_at` TIMESTAMP
 * column. Bow's migration helper `$table->addSoftDelete()` adds it.
 *
 * The column name can be customised by declaring
 *     `protected string $deleted_at = 'archived_on';`
 * on the model.
 */
trait SoftDelete
{
    /**
     * Soft-delete this record by stamping the `deleted_at` column.
     *
     * Fires the standard `model.deleting` / `model.deleted` events so existing
     * listeners keep working. Returns the number of affected rows (0 if the
     * record had no primary-key value, was missing from the table, or is
     * already trashed).
     *
     * @return int
     */
    public function delete(): int
    {
        $primary_key_value = $this->getKeyValue();

        if ($primary_key_value === null) {
            return 0;
        }

        $builder = static::query();

        if (!$builder->exists($this->primary_key, $primary_key_value)) {
            return 0;
        }

        $this->fireEvent('model.deleting');

        $now = date('Y-m-d H:i:s');

        $updated = $builder->where($this->primary_key, $primary_key_value)
            ->update([$this->getDeletedAtColumn() => $now]);

        if ($updated) {
            $this->attributes[$this->getDeletedAtColumn()] = $now;
            $this->fireEvent('model.deleted');
        }

        return $updated;
    }

    /**
     * Restore a soft-deleted record by clearing its `deleted_at` column.
     *
     * Fires `model.restoring` / `model.restored` events. Returns true on
     * success.
     */
    public function restore(): bool
    {
        $primary_key_value = $this->getKeyValue();

        if ($primary_key_value === null) {
            return false;
        }

        $this->fireEvent('model.restoring');

        $restored = static::query()
            ->where($this->primary_key, $primary_key_value)
            ->update([$this->getDeletedAtColumn() => null]);

        if ($restored) {
            $this->attributes[$this->getDeletedAtColumn()] = null;
            $this->fireEvent('model.restored');
        }

        return (bool) $restored;
    }

    /**
     * Force a physical DELETE that bypasses soft delete entirely.
     *
     * Fires `model.forceDeleting` / `model.forceDeleted` (the standard
     * `model.deleting` / `model.deleted` are NOT fired by this method —
     * subscribe to the force-delete events when you need to react to it).
     */
    public function forceDelete(): int
    {
        $primary_key_value = $this->getKeyValue();

        if ($primary_key_value === null) {
            return 0;
        }

        $this->fireEvent('model.forceDeleting');

        $deleted = static::query()
            ->where($this->primary_key, $primary_key_value)
            ->delete();

        if ($deleted) {
            $this->fireEvent('model.forceDeleted');
        }

        return $deleted;
    }

    /**
     * Whether this instance has been soft-deleted.
     */
    public function trashed(): bool
    {
        return !is_null($this->attributes[$this->getDeletedAtColumn()] ?? null);
    }

    /**
     * Resolve the `deleted_at` column name, honouring an optional
     * `protected string $deleted_at = '...';` override on the model.
     */
    public function getDeletedAtColumn(): string
    {
        return property_exists($this, 'deleted_at') && is_string($this->deleted_at)
            ? $this->deleted_at
            : 'deleted_at';
    }

    /**
     * Start a query that excludes soft-deleted rows.
     *
     *     User::withoutTrashed()->where('active', true)->get();
     */
    public static function withoutTrashed(): Builder
    {
        $instance = new static();
        return static::query()->whereNull($instance->getDeletedAtColumn());
    }

    /**
     * Start a query that only returns soft-deleted rows.
     */
    public static function onlyTrashed(): Builder
    {
        $instance = new static();
        return static::query()->whereNotNull($instance->getDeletedAtColumn());
    }

    /**
     * Start a query that includes both active and soft-deleted rows.
     *
     * This is equivalent to `static::query()` and is provided as a readable
     * intent marker.
     */
    public static function withTrashed(): Builder
    {
        return static::query();
    }

    /**
     * Register a `model.restoring` listener.
     */
    public static function restoring(callable $cb): void
    {
        event()->once(static::formatEventName('model.restoring'), $cb);
    }

    /**
     * Register a `model.restored` listener.
     */
    public static function restored(callable $cb): void
    {
        event()->once(static::formatEventName('model.restored'), $cb);
    }

    /**
     * Register a `model.forceDeleting` listener.
     */
    public static function forceDeleting(callable $cb): void
    {
        event()->once(static::formatEventName('model.forceDeleting'), $cb);
    }

    /**
     * Register a `model.forceDeleted` listener.
     */
    public static function forceDeleted(callable $cb): void
    {
        event()->once(static::formatEventName('model.forceDeleted'), $cb);
    }
}
