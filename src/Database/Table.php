<?php

namespace System\Database;

class Table
{
    private $tableName;
    private $connection;
    // contructeur
    public function __construct($tableName, $connection) {}
    // contructeur de requete.
    public function select(...$sqlstement) {}
    public function where($column, $comp, $value) {}
    public function orWhere($cb) {}
    public function whereNull($column) {}
    public function whereNotNull($column, $range) {}
    public function whereBetween($column, $range) {}
    public function whereNotBetween($column, $range) {}
    public function whereIn($column, $range) {}
    public function whereNotIn($column, $range) {}
    public function join($table) {}
    public function leftJoin($table) {}
    public function rightJoin($table) {}
    public function on($condition) {}
    public function orOn($condition) {}
    public function groupBy($column) {}
    public function orderBy($column, $type) {}
    public function jump($offset) {}
    public function take($limit) {}
    // Les Aggregats
    public function max($column) {}
    public function min($column) {}
    public function avg($column) {}
    public function sum($column) {}
    public function count($column) {}
    public function upper($column) {}
    public function lower($column) {}
    public function concat(...$column) {}
    // Actionner
    public function get() {}
    public function update() {}
    public function delete() {}
    public function increment($column, $step = null) {}
    public function decrement($column, $step = null) {}
    public function truncate($column, $step = null) {}
    public function insert($values) {}
    public function insertAndGetId($values) {}
    public function first() {}
}
