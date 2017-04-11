<?php
namespace Bow\Database\Model;

use Bow\Database\QueryBuilder\QueryBuilderTrait;

class ModelQueryBuilder
{
    use QueryBuilderTrait;

    /**
     * @var bool
     */
    protected $timestamps = false;

    /**
     * @var array
     */
    protected $attributes = [];

    /**
     * @var string
     */
    protected $primary = 'id';

    /**
     * Permet de sauvegarder des informations
     */
    public function save()
    {
        $sql = $this->where($this->primary, $this->attributes[$this->primary])->getSelectStatement();
    }
}