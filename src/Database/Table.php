<?php

namespace System\Database;

use System\Exception\TableException;

class Table
{
    /**
     * @var
     */
    private $tableName;
    /**
     * @var
     */
    private $connection;
    /**
     * @var string
     */
    private static $select = null;
    /**
     * @var string
     */
    private static $where = null;
    /**
     * @var string
     */
    private static $group_by = null;
    /**
     * @var string
     */
    private static $join = null;
    /**
     * @var string
     */
    private static $limit = null;
    /**
     * @var string
     */
    private static $aggregats = [];
    /**
     * @var null
     */
    private static $instance;
    
    // contructeur
    private function __construct($tableName, $connection)
    {
        $this->connection = $connection;
        $this->tableName = $tableName;
    }
    private function __clone() {}

    /**
     * Charge le singleton
     *
     * @param $tableName
     * @param $connection
     * @return Table
     */
    public static function load($tableName, $connection)
    {
        if (self::$instance === null) {
            self::$instance = new self($tableName, $connection);
        }
        return self::$instance;
    }

    // contructeur de requete.
    /**
     * select, ajout de champ a selection.
     * 
     * @param null $column
     * @return $this
     */
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

    /**
     * where, ajout condition de type where, si chaine ajout un and
     * 
     * @param $column
     * @param $comp
     * @param null $value
     * @return $this
     */
    public function where($column, $comp = "=", $value = null, $boolean = "and")
    {
        if (!static::isComporaisonOperator($comp)) {
            $value = $comp;
            $comp = "=";
        } else {
            if (is_null($value)) {
                throw new TableException(__METHOD__."(), valeur non définir", E_ERROR);
            }
        }

        if (static::$where == null) {
            static::$where = "$column $comp $value";
        } else {
            static::$where .= " $boolean $column $comp $value";
        }
        return $this;
    }

    /**
     * @param $column
     * @param $comp
     * @param null $value
     * @return $this
     */
    public function orWhere($column, $comp, $value = null)
    {
        if (is_null(static::$where)) {
            throw new TableException(__METHOD__."(), ne peut pas être utiliser sans un where avant", E_ERROR);
        }
        $this->where("$column", $comp, $value, "or");
        return $this;
    }

    /**
     * @param $column
     * @param $value
     */
    public function whereNull($column, $boolean = "and")
    {
        if (!is_null(static::$where)) {
            static::$where = "$column is null";
        } else {
            static::$where = " $boolean $column is null";
        }
        return $this;
    }

    /**
     * @param $column
     * @param string $boolean
     */
    public function whereNotNull($column, $boolean = "and")
    {
        if (is_null(static::$where)) {
            static::$where = "$column is not null";
        } else {
            static::$where .= " $boolean $column is not null";
        }
        return $this;
    }

    /**
     * @param $column
     * @param $range
     * @return $this
     */
    public function whereBetween($column, array $range, $boolean = "and")
    {
        if (count($range) > 2) {
            $range = array_slice($range, 0, 2);
        } else {
            if (count($range) == 0) {
                throw new TableException(__METHOD__."(). le paramètre 2 ne doit pas être un tableau vide.", E_ERROR);
            }
            $range = [$range[0], $range[0]];
        }
        $between = implode(" and ", $range);
        if (is_null(static::$where)) {
            if ($boolean == "not" || $boolean == "and not") {
                static::$where = "not $column between " . $between;
            } else {
                static::$where = "$column between " . $between;
            }
        } else {
            static::$where .= " $boolean $column is not null";
        }
        return $this;
    }

    /**
     * @param $column
     * @param $range
     * @return $this
     */
    public function whereNotBetween($column, array $range)
    {
        $this->whereBetween($column, $range, "and not");
        return $this;
    }

    /**
     * @param $column
     * @param $range
     * @return $this
     */
    public function whereIn($column, array $range, $between = "and")
    {
        if (count($range) > 2) {
            $range = array_slice($range, 0, 2);
        } else {
            if (count($range) == 0) {
                throw new TableException(__METHOD__."(). le paramètre 2 ne doit pas être un tableau vide.", E_ERROR);
            }
            $range = [$range[0], $range[0]];
        }
        $in = implode(", ", $range);
        if (is_null(static::$where)) {
            if ($between == "not" || $between == "and not") {
                static::$where = "not $column in ($in)";
            } else {
                static::$where .= " and not $column in ($in)";
            }
        } else {
            static::$where .= " $boolean $column in ($in)";
        }
        return $this;
    }

    /**
     * @param $column
     * @param $range
     * @return $this
     */
    public function whereNotIn($column, array $range)
    {   if (is_null(static::$where)) {
            throw new TableException(__METHOD__."(), ne peut pas être utiliser sans un whereIn avant", E_ERROR);
        }
        $this->whereIn($column, $range, "and not");
        return $this;
    }

    /**
     * @param $table
     * @return $this
     */
    public function join($table)
    {
        return $this;
    }

    /**
     * @param $table
     * @return $this
     */
    public function leftJoin($table)
    {
        return $this;
    }

    /**
     * @param $table
     * @return $this
     */
    public function rightJoin($table)
    {
        return $this;
    }

    /**
     * @param $condition
     * @return $this
     */
    public function on($condition)
    {
        return $this;
    }

    /**
     * @param $condition
     * @return $this
     */
    public function orOn($condition)
    {
        return $this;
    }

    /**
     * @param $column
     * @return $this
     */
    public function groupBy($column)
    {
        return $this;
    }

    /**
     * @param $column
     * @param $type
     * @return $this
     */
    public function orderBy($column, $type)
    {
        return $this;
    }

    /**
     * jump = offset
     *
     * @param $offset
     * @return $this
     */
    public function jump($offset = 0)
    {
        $this->jump = $offset;
        return $this;
    }

    /**
     * take = limit
     *
     * @param $limit
     * @return $this
     */
    public function take($limit)
    {
        $this->limit = $limit;
        return $this;
    }
    // Les Aggregats
    /**
     * Max
     *
     * @param $column
     */
    public function max($column)
    {
        return $this->addAggregat("max", $column);
    }

    /**
     * Min
     *
     * @param $column
     */
    public function min($column)
    {
        return $this->addAggregat("min", $column);
    }

    /**
     * Avg
     *
     * @param $column
     */
    public function avg($column)
    {
        return $this->addAggregat("avg", $column);
    }

    /**
     * @param $column
     */
    public function sum($column)
    {
        return $this->addAggregat("sum", $column);
    }


    /**
     * @param $column
     */
    public function upper($column)
    {
        return $this->addAggregat("upper", $column);
    }

    /**
     * @param $column
     */
    public function lower($column)
    {
        return $this->addAggregat("lower", $column);
    }

    /**
     * @param $column
     */
    public function concat($column)
    {

    }

    private function addAggregat($name, $value)
    {
        if (!isset(static::$aggregats[$name])) {
            static::$aggregats[$name] = $value;
        }
        return $this;
    }

    // Actionner
    /**
     * Action get, seulement sur la requete de type select
     * 
     * @param $limit
     * @return array|object|
     */
    public function get($limit = null)
    {
        $sql = "select";
        $fetch = "fetchAll";
        if (is_null(static::$select)) {
            $sql .= " * from " . $this->tableName;
            if (is_int($limit)) {
                if ($limit === 1) {
                    $fetch = "fetch";
                } 
                $sql .= " limit " . (int) $limit;
            }
            return $this->connection->query($sql)->$fetch();
        }
        return null;
    }

    /**
     * @param $column
     */
    public function count($column = "*")
    {
        return (int) $this->connection->query("select count($column) from " . $this->tableName)->fetchColumn();
    }

    /**
     * Action update
     */
    public function update()
    {

    }

    /**
     * Action delete
     */
    public function delete()
    {

    }

    /**
     * Action increment, ajout 1 par defaut sur le champs spécifié
     *
     * @param $column
     * @param int $step
     */
    public function increment($column, $step = 1)
    {

    }


    /**
     * Action decrement, soustrait 1 par defaut sur le champs spécifié
     *
     * @param $column
     * @param int $step
     */
    public function decrement($column, $step = 1)
    {

    }

    /**
     * Action truncate, vide la table
     */
    public function truncate()
    {
        if (is_null(static::$select) && is_null(static::$where) 
            && is_null(static::$group) && is_null(static::$group_by)
            && is_null(static::$join) && is_null(static::$order)
            && is_null(static::$havin) && is_null(static::$update)
            && is_null(static::$delete) && is_null(static::$insert)) {

            return $this->connection->exec("truncate " . $this->tableName);
        } else {
            // Throws
        }
    }
    /**
     * Action insert
     *
     * @param $values
     */
    public function insert($values)
    {

    }

    /**
     * Action insertAndGetLastId
     * lance les actions insert et lastInsertId
     *
     * @param $values
     */
    public function insertAndGetLastId($values)
    {

    }

    /**
     * Action first, récupère le première enregistrement
     */
    public function first()
    {

    }

    /**
     * Action drop, supprime la table
     */
    public function drop()
    {
        if (is_null(static::$select) && is_null(static::$where) 
            && is_null(static::$group) && is_null(static::$group_by)
            && is_null(static::$join) && is_null(static::$order)
            && is_null(static::$havin) && is_null(static::$update)
            && is_null(static::$delete) && is_null(static::$insert)) {

            return $this->connection->exec("drop " . $this->tableName);
        } else {
            // Throws
        }
    }

    /**
     * Utilitaire isComporaisonOperator, permet valider un comparateur
     *
     * @param $comp
     * @return bool
     */
    private static function isComporaisonOperator($comp)
    {
        if (in_array($comp, ["=", ">", "<", ">=", "=<", "<>", "!="])) {
            return true;
        }
        return false;
    }

    /**
     * Utilitaire isBooleanOperator, permet valider un boolean
     *
     * @param $comp
     * @return bool
     */
    private static function isBooleanOperator($comp)
    {
        if (in_array($comp, ["and", "or"])) {
            return true;
        }
        return false;
    }
}
