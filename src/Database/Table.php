<?php

namespace System\Database;

class Table
{
    private $tableName;
    private $connection;
    private static $select = "*";
    private static $where = "";
    
    // contructeur
    public function __construct($tableName, $connection) {}
    
    // contructeur de requete.
    public function select($column = null) {
        if (func_num_args() > 1) {
            $column = implode(", ", func_get_args());
        }
        if (is_array($column)) {
            $column = implode(", ", $column);
        }
        if (!is_null($column)) {
            static::$select = $column;
        }
        return $this;
    }

    public function where($column, $comp, $value = null)
    {
        if (!static::isComporaisonOperator($comp)) {
            $value = $comp;
            $comp = "=";
        }
        static::$where .= " $column $comp $value";
        return $this;
    }
    
    public function orWhere($column, $comp, $value = null)
    {
        $this->where(" or $column", $comp, $value);
        return $this;
    }

    public function whereNull($column)
    {
        static::$where .= " $column is null $value";
    }
    public function whereNotNull($column, $boolean = "and")
    {
        if (strlen(static::$where) == 0) {
            static::$where = "$column is not null";
        } else {
            static::$where .= " $boolean $column is not null"
        }
    }

    public function whereBetween($column, $range)
    {
        return $this;
    }

    public function whereNotBetween($column, $range)
    {
        return $this;
    }

    public function whereIn($column, $range)
    {
        return $this;
    }

    public function whereNotIn($column, $range)
    {
        return $this;
    }

    public function join($table)
    {
        return $this;
    }

    public function leftJoin($table)
    {
        return $this;
    }

    public function rightJoin($table)
    {
        return $this;
    }

    public function on($condition)
    {
        return $this;
    }

    public function orOn($condition)
    {
        return $this;
    }

    public function groupBy($column)
    {
        return $this;
    }

    public function orderBy($column, $type)
    {
        return $this;
    }

    public function jump($offset)
    {
        return $this;
    }

    public function take($limit)
    {
        return $this;
    }
    // Les Aggregats
    public function max($column)
    {

    }

    public function min($column)
    {

    }

    public function avg($column)
    {

    }

    public function sum($column)
    {

    }

    public function count($column)
    {

    }

    public function upper($column)
    {

    }

    public function lower($column)
    {

    }

    public function concat(...$column)
    {

    }

    // Actionner
    public function get()
    {

    }

    public function update()
    {

    }

    public function delete()
    {

    }

    public function increment($column, $step = null)
    {

    }

    public function decrement($column, $step = null)
    {

    }

    public function truncate($column, $step = null)
    {

    }

    public function insert($values)
    {

    }

    public function insertAndGetLastId($values)
    {

    }

    public function insertAndGetCurrentId($values)
    {

    }

    public function first()
    {

    }

    public function drop()
    {

    }

    // Utilitaire
    private static function isComporaisonOperator($comp)
    {
        if (in_array($comp, ["=", ">", "<", ">=", "=<", "<>", "!="])) {
            return true;
        }
        return false;
    }
}
