<?php
namespace Bow\Database;

class Collection extends \Bow\Support\Collection
{
    /**
     * Collection constructor.
     * @param array $arr
     */
    public function __construct(array $arr = [])
    {
        parent::__construct($arr);
    }

    /**
     * @return array
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
     * Permet de supprimer tout les enrégistrement séléctionnés
     */
    public function dropAll()
    {
        $this->each(function (Model $model) {
            $model->delete();
        });
    }
}