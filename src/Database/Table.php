<?php

namespace System\Database;

use System\Exception\TableException;
use System\Support\Security;

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
    private $select = null;
    /**
     * @var string
     */
    private $where = null;
    /**
     * @var string
     */
    private $join = null;
    /**
     * @var string
     */
    private $limit = null;
    /**
     * @var string
     */
    private $group = null;
    /**
     * @var string
     */
    private $insert = null;
    /**
     * @var string
     */
    private $havin = null;
    /**
     * @var string
     */
    private $order = null;
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

        if ($this->where == null) {
            $this->where = "$column $comp $value";
        } else {
            $this->where .= " $boolean $column $comp $value";
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
        if (is_null($this->where)) {
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
        if (!is_null($this->where)) {
            $this->where = "$column is null";
        } else {
            $this->where = " $boolean $column is null";
        }
        return $this;
    }

    /**
     * @param $column
     * @param string $boolean
     */
    public function whereNotNull($column, $boolean = "and")
    {
        if (is_null($this->where)) {
            $this->where = "$column is not null";
        } else {
            $this->where .= " $boolean $column is not null";
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
        if (is_null($this->where)) {
            if ($boolean == "not" || $boolean == "and not") {
                $this->where = "not $column between " . $between;
            } else {
                $this->where = "$column between " . $between;
            }
        } else {
            $this->where .= " $boolean $column is not null";
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
        if (is_null($this->where)) {
            if ($between == "not" || $between == "and not") {
                $this->where = "not $column in ($in)";
            } else {
                $this->where .= " and not $column in ($in)";
            }
        } else {
            $this->where .= " $boolean $column in ($in)";
        }
        return $this;
    }

    /**
     * @param $column
     * @param $range
     * @return $this
     */
    public function whereNotIn($column, array $range)
    {   if (is_null($this->where)) {
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
    	if (is_null($this->limit)) {
	        $this->limit = "$offset,";
    	}
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
    	if (is_null($this->limit)) {
	        $this->limit = $limit;
    	} else {
    		if (preg_match("/^([\d]+),$/", $this->limit, $match)) {
    			array_shift($match);
    			$this->limit = "{$match[0]}, $limit";
    		}
    	}
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
        return $this->executeAgregat("max", $column);
    }

    /**
     * Min
     *
     * @param $column
     */
    public function min($column)
    {
        return $this->executeAgregat("min", $column);
    }

    /**
     * Avg
     *
     * @param $column
     */
    public function avg($column)
    {
        return $this->executeAgregat("avg", $column);
    }

    /**
     * @param $column
     */
    public function sum($column)
    {
        return $this->executeAgregat("sum", $column);
    }

    /**
     * Lance en interne les requetes utilistance les aggregats.
     * 
     * @param $aggregat
     * @param string $column
     * @return null|int
     */
    private function executeAgregat($aggregat, $column)
    {
        $sql = "select $aggregat($column) from " . $this->tableName;
    	if (!is_null($this->where)) {
    		$sql .= " " . $this->where;
    		$this->where = null;
    	}
    	$s = $this->connection->prepare($sql);
    	$s->execute();
    	return (int) $s->fetchColumn();
    }

    // Actionner
    /**
     * Action get, seulement sur la requete de type select
     * 
     * @param $limit
     * @return array|object|
     */
    public function get()
    {
        $sql = "select ";
        $fetch = "fetchAll";
       	// Ajout de la clause select
        if (is_null($this->select)) {
            $sql .= "* from " . $this->tableName;
        } else {
        	$sql .= $this->select . " from ";
        	$this->select = null;
        }
        // Ajout de la clause join
        if (!is_null($this->join)) {
        	$sql .= " join " . $this->join;
        	$this->join = null;
        }
        // Ajout de la clause where
        if (!is_null($this->where)) {
        	$sql .= " where " . $this->where;
        	$this->where = null;
        }
        // Ajout de la clause order
        if (!is_null($this->order)) {
        	$sql .= " order by " . $this->order;
        	$this->order = null;
        }
        // Ajout de la clause limit
        if (!is_null($this->limit)) {
	        $sql .= " limit " . $this->limit;
	        $this->limit = null;
        }
        // Ajout de la clause group
        if (!is_null($this->group)) {
        	$sql .= " group by " . $this->group;
        	$this->group = null;
        }
        // execution de requete.
        $stmt = $this->connection->prepare($sql);
        $stmt->execute();

        if ($stmt->rowCount() <= 1) {
        	$fetch = "fetch";
        }

        return Security::sanitaze($stmt->$fetch());
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
    public function update(array $data = [])
    {
		$sql = "update " . $this->tableName . " set ";
		$i = 0;
		foreach ($data as $key => $value) {
			Security::sanitaze($value, true);
			if ($i > 0) {
				$sql .= ", ";
			}
			$sql .= "$key = :$key";
			$i++;
		}

		if (!is_null($this->where)) {
			$sql .= " where " . $this->where;
		}

		$stmt = $this->connection->prepare($sql);
		$stmt->execute($data);
		$this->where = null;
		
		return $stmt->rowCount();
    }

    /**
     * Action delete
     */
    public function delete(array $where = [])
    {
		$sql = "delete from " . $this->tableName;
		$i = 0;

		if (!is_null($this->where)) {
			$sql .= " where " . $this->where;
		}
		$stmt = $this->connection->prepare($sql);
		$stmt->execute($where);
		$this->where = null;
		return $stmt->rowCount();
    }

    /**
     * Action increment, ajout 1 par defaut sur le champs spécifié
     *
     * @param $column
     * @param int $step
     */
    public function increment($column, $step = 1)
    {
    	return $this;
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
        return (bool) $this->connection->exec("truncate " . $this->tableName);
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
        return (bool) $this->connection->exec("drop table " . $this->tableName);
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
