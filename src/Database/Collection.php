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
}