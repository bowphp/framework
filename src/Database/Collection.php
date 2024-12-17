<?php

declare(strict_types=1);

namespace Bow\Database;

use Bow\Support\Collection as SupportCollection;
use Bow\Database\Barry\Model;

class Collection extends SupportCollection
{
    /**
     * @inheritdoc
     */
    public function __construct(array $storage = [])
    {
        parent::__construct($storage);
    }

    /**
     * Get the first item of starage
     *
     * @return ?Model
     */
    public function first(): ?Model
    {
        $result = parent::first();

        return $result !== false ? $result : null;
    }

    /**
     * @inheritdoc
     */
    public function toArray(): array
    {
        $arr = [];

        foreach ($this->storage as $value) {
            $arr[] = $value->toArray();
        }

        return $arr;
    }

    /**
     * @inheritdoc
     */
    public function toJson(int $option = 0): string
    {
        $data = [];

        foreach ($this->toArray() as $model) {
            $data[] = $model->toArray();
        }

        return json_encode($data, $option = 0);
    }

    /**
     * Allows you to delete all the selected recordings
     *
     * @return void
     */
    public function dropAll(): void
    {
        $this->each(function (Model $model) {
            $model->delete();
        });
    }

    /**
     * @inheritdoc
     */
    public function __toString(): string
    {
        return json_encode($this->all());
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize(): array
    {
        return $this->all();
    }
}
