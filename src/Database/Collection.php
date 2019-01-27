<?php

namespace Bow\Database;

use Bow\Database\Barry\Model;

class Collection extends \Bow\Support\Collection
{
    /**
     * @inheritdoc
     */
    public function __construct(array $arr = [])
    {
        parent::__construct($arr);
    }

    /**
     * @inheritdoc
     */
    public function toArray()
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
    public function toJson($option = 0)
    {
        return  json_encode($this->toArray(), $option = 0);
    }

    /**
     * Allows you to delete all the selected recordings
     *
     * @return void
     */
    public function dropAll()
    {
        $this->each(function (Model $model) {
            $model->delete();
        });
    }

    /**
     * @inheritdoc
     */
    public function __toString()
    {
        return json_encode($this->toArray());
    }

    /**
     * @inheritdoc
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }
}
