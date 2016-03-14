<?php

namespace Bow\Database;

use Bow\Support\Security;
use Bow\Support\Collection;
use Bow\Exception\TableException;
use Bow\Support\Session;

class Table extends DatabaseTools
{
    /**
     * @var string
     */
    private $tableName;

    /**
     * @var string
     */
    private static $_tableName;

    /**
     * @var \PDO
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
     * @var array
     */
    private $whereDataBind = [];
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
    private $havin = null;

    /**
     * @var string
     */
    private $order = null;

    /**
     * @var null
     */
    private static $instance;
    
    /**
     * Contructeur
     * 
     * @param string $tableName
     * @param $connection
     */
    private function __construct($tableName, $connection)
    {
        $this->connection = $connection;
        $this->tableName = self::$_tableName = $tableName;
    }

    // fonction magic __clone
    private function __clone() {}

    /**
     * Charge le singleton
     *
     * @param $tableName
     * @param $connection
     * 
     * @return Table
     */
    public static function load($tableName, $connection)
    {
        if (self::$instance === null || self::$_tableName != $tableName) {
            self::$instance = new self($tableName, $connection);
        }

        return self::$instance;
    }

    /**
     * select, ajout de champ à séléction.
     * 
     * @param null $column
     * @return $this
     */
    public function select($column = null) {

        if (is_array($column)) {
            $column = implode(", ", $column);
        } else {
            if (func_num_args() >= 1) {
                $column = implode(", ", func_get_args());
            }
        }

        if (!is_null($column)) {
            $this->select = $column;
        }

        return $this;
    }

    /**
     * where, ajout condition de type where, si chainé ajout un <<and>>
     *
     * @param $column
     * @param $comp
     * @param null $value
     * @param $boolean
     *
     * @throws TableException
     *
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

        $this->whereDataBind[$column] = $value;

        if ($this->where == null) {
            $this->where = "$column $comp :$column";
        } else {
            $this->where .= " $boolean $column $comp :$column";
        }

        return $this;
    }

    /**
     * orWhere, retourne une condition de type [where colonne = value <<or colonne = value>>]
     *
     * @param string $column
     * @param string $comp
     * @param null $value
     *
     * @throws TableException
     *
     * @return Table
     */
    public function orWhere($column, $comp = "=", $value = null)
    {
        if (is_null($this->where)) {
            throw new TableException(__METHOD__."(), ne peut pas être utiliser sans un where avant", E_ERROR);
        }

        $this->where($column, $comp, $value, "or");

        return $this;
    }

    /**
     * clause where avec comparaison en <<is null>>
     *
     * @param string $column
     * @param string $boolean="and"
     *
     * @return $this
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
     * clause where avec comparaison en <<not null>>
     *
     * @param $column
     * @param string $boolean="and|or"
     *
     * @return $this
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
     * clause where avec comparaison en <<between>>
     *
     * @param $column
     * @param array $range
     * @param string boolean="and|or"
     *
     * @throws TableException
     *
     * @return $this
     */
    public function whereBetween($column, array $range, $boolean = "and")
    {

        if (count($range) !== 2) {
            throw new TableException(__METHOD__."(). le paramètre 2 ne doit pas être un tableau vide.", E_ERROR);
        }

        $between = implode(" and ", $range);

        if (is_null($this->where)) {
            if ($boolean == "not") {
                $this->where = "$column not between " . $between;
            } else {
                $this->where = "$column between " . $between;
            }
        } else {
            if ($boolean == "not") {
                $this->where .= " and $column not between $between";
            } else {
                $this->where .= " $boolean $column between $between";
            }
        }

        return $this;
    }

    /**
     *
     *
     * @param $column
     * @param $range
     * @return $this
     */
    public function whereNotBetween($column, array $range)
    {
        $this->whereBetween($column, $range, "not");

        return $this;
    }

    /**
     * clause where avec comparaison en <<in>>
     *
     * @param string $column
     * @param array $range
     * @param string $boolean
     *
     * @throws TableException
     *
     * @return $this
     */
    public function whereIn($column, array $range, $boolean = "and")
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
            if ($boolean == "not") {
                $this->where = "$column not in ($in)";
            } else {
                $this->where = "$column in ($in)";
            }
        } else {
            if ($boolean == "not") {
                $this->where .= " and $column not in ($in)";
            } else {
                $this->where .= " and $column in ($in)";
            }
        }

        return $this;
    }

    /**
     * clause where avec comparaison en <<not in>>
     *
     * @param string $column
     * @param array $range
     *
     * @throws TableException
     *
     * @return $this
     */
    public function whereNotIn($column, array $range)
    {
        $this->whereIn($column, $range, "not");

        return $this;
    }

    /**
     * clause join
     *
     * @param $table
     *
     * @return $this
     */
    public function join($table)
    {
        if (is_null($this->join)) {
            $this->join = "inner join $table";
        } else {
            $this->join .= ", $table";
        }

        return $this;
    }

    /**
     * clause left join
     *
     * @param $table
     *
     * @throws TableException
     *
     * @return $this
     */
    public function leftJoin($table)
    {
        if (is_null($this->join)) {
            $this->join = "left join $table";
        } else {
            if (!preg_match("/^(inner|right)\sjoin\s.*/", $this->join)) {
                $this->join .= ", $table";
            } else {
                throw new TableException("la clause inner join est dèja activé.", E_ERROR);
            }
        }

        return $this;
    }

    /**
     * clause right join
     *
     * @param $table
     * @throws TableException
     * @return $this
     */
    public function rightJoin($table)
    {
        if (is_null($this->join)) {
            $this->join = "right join $table";
        } else {
            if (!preg_match("/^(inner|left)\sjoin\s.*/", $this->join)) {
                $this->join .= ", $table";
            } else {
                throw new TableException("la clause inner join est dèja activé.", E_ERROR);
            }
        }
        return $this;
    }

    /**
     * On, Si chainé avec lui même doit ajouter un <<and>> avant, sinon
     * si chainé avec <<orOn>> orOn ajout un <<or>> dévant
     *
     * @param string $column1
     * @param string $comp
     * @param string $column2
     *
     * @throws TableException
     *
     * @return $this
     */
    public function on($column1, $comp = "=", $column2)
    {
        if (is_null($this->join)) {
            throw new TableException("la clause inner join est dèja activé.", E_ERROR);
        }

        if (!$this->isComporaisonOperator($comp)) {
            $column2 = $comp;
        }

        if (!preg_match("/on/i", $this->join)) {
            $this->join .= " on $column1 $comp $column2";
        }

        return $this;
    }

    /**
     * clause On, suivie d'une combinaison par un comparateur <<or>>
     * Il faut que l'utilisateur fasse un <<on()>> avant d'utiliser le <<orOn>>
     *
     * @param string $column
     * @param string $comp
     * @param string $value
     *
     * @throws TableException
     *
     * @return $this
     */
    public function orOn($column, $comp = "=", $value)
    {
        if (is_null($this->join)) {
            throw new TableException("la clause inner join est dèja activé.", E_ERROR);
        }

        if (!$this->isComporaisonOperator($comp)) {
            $value = $comp;
        }

        if (preg_match("/on/i", $this->join)) {
            $this->join .= " or $column $comp $value";
        } else {
            throw new TableException("la clause on n'est pas activé.", E_ERROR);
        }

        return $this;
    }

    /**
     * clause group by
     *
     * @param string $column
     *
     * @return $this
     */
    public function group($column)
    {
        if (is_null($this->group)) {
            $this->group = "group $column";
        }

        return $this;
    }

    /**
     * clause order by
     *
     * @param string $column
     * @param string $type
     *
     * @return $this
     */
    public function order($column, $type = "asc")
    {
        if (is_null($this->order)) {
            if (!in_array($type, ["asc", "desc"])) {
                $type = "asc";
            }

            $this->order = "order by $column $type";
        }

        return $this;
    }

    /**
     * jump = offset
     *
     * @param int $offset
     * @return $this
     */
    public function jump($offset = 0)
    {
    	if (is_null($this->limit)) {
            $this->limit = "$offset, ";
        }

        return $this;
    }

    /**
     * take = limit
     *
     * @param int $limit
     * 
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

    /**
     * Max
     *
     * @param string $column
     * 
     * @return Table
     */
    public function max($column)
    {
        return $this->executeAgregat("max", $column);
    }

    /**
     * Min
     *
     * @param string $column
     * 
     * @return Table
     */
    public function min($column)
    {
        return $this->executeAgregat("min", $column);
    }

    /**
     * Avg
     *
     * @param string $column
     * 
     * @return Table
     */
    public function avg($column)
    {
        return $this->executeAgregat("avg", $column);
    }

    /**
     * Sum
     *
     * @param string $column
     * 
     * @return Table
     */
    public function sum($column)
    {
        return $this->executeAgregat("sum", $column);
    }

    /**
     * Lance en interne les requêtes utilisants les aggregats.
     * 
     * @param $aggregat
     * @param string $column
     * 
     * @return null|int
     */
    private function executeAgregat($aggregat, $column)
    {
        $sql = "select $aggregat($column) from " . $this->tableName;
    	
        if (!is_null($this->where)) {
    		$sql .= " where " . $this->where;
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
     * @param callable $cb
     * 
     * @return mixed
     */
    public function get($cb = null)
    {
        $sql = "select ";

       	// Ajout de la clause select
        if (is_null($this->select)) {
            $sql .= "* from " . $this->tableName;
        } else {
        	$sql .= $this->select . " from " . $this->tableName;
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
        	$sql .= " " . $this->order;
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
        static::bind($stmt, $this->whereDataBind);
        $stmt->execute();

        $data = Security::sanitaze($stmt->fetchAll());

        if (is_callable($cb)) {
        	return call_user_func_array($cb, [$data]);
        }
        
        return $data;
    }

    /**
     * count
     * 
     * @param string $column
     * @param callable $cb=null
     * 
     * @return int
     */
    public function count($column = "*", $cb = null)
    {
    	if (is_callable($column)) {
    		$cb = $column;
    		$column = "*";
    	}

        $sql = "select count($column) from " . $this->tableName;

        if ($this->where !== null) {
            $sql .= " where " . $this->where;
            $this->where = null;
        }

    	$stmt = $this->connection->prepare($sql);
        static::bind($stmt, $this->whereDataBind);
        $stmt->execute();
        $count = $stmt->fetchColumn();

    	if (is_callable($cb)) {
    		call_user_func_array($cb, [$count]);
    	}

        return  $count;
    }

    /**
     * Action update
     *
     * @param array $data
     * @param callable $cb
     * 
     * @return int
     */
    public function update(array $data = [], $cb = null)
    {
		$sql = "update " . $this->tableName . " set ";
		$data = Security::sanitaze($data, true);
		$sql .= parent::rangeField(parent::add2points(array_keys($data)));

		if (!is_null($this->where)) {
			$sql .= " where " . $this->where;
			$this->where = null;
		}

		$stmt = $this->connection->prepare($sql);
		static::bind($stmt, $data);
		$stmt->execute();

		$r = $stmt->rowCount();

		if (is_callable($cb)) {
        	return call_user_func_array($cb, [$r]);
        }

		return $r;
    }

    /**
     * Action delete
     *
     * @param callable $cb
     * 
     * @return int
     */
    public function delete($cb = null)
    {
		$sql = "delete from " . $this->tableName;

		if (!is_null($this->where)) {
			$sql .= " where " . $this->where;
	        $this->where = null;
		}

		$stmt = $this->connection->prepare($sql);

		static::bind($stmt, $this->whereDataBind);
		$stmt->execute();
		
		$data = $stmt->rowCount();

        if (is_callable($cb)) {
        	return call_user_func_array($cb, [$data]);
        }

        return $data;
    }

    /**
     * Action increment, ajout 1 par défaut sur le champs spécifié
     *
     * @param $column
     * @param int $step
     * 
     * @return Table
     */
    public function increment($column, $step = 1)
    {
        return $this->crement($column, $step, "+");
    }


    /**
     * Action decrement, soustrait 1 par defaut sur le champs spécifié
     *
     * @param string $column
     * @param int $step
     * 
     * @return int|bool
     */
    public function decrement($column, $step = 1)
    {
        return $this->crement($column, $step, "-");
    }

    /**
     * method permettant de customiser les methods increment et decrement
     *
     * @param string $column
     * @param int $step
     * @param string $sign
     * 
     * @return int
     */
    private function crement($column, $step = 1, $sign = "")
    {
        $sql = "update " . $this->tableName . " set $column = $column $sign $step";

        if (!is_null($this->where)) {
            $sql .= " " . $this->where;
            $this->where = null;
        }

        $stmt = $this->connection->prepare($sql);
        $this->bind($stmt, $this->whereDataBind);
        $stmt->execute();

        return (int) $stmt->rowCount();
    }

    /**
     * Action truncate, vide la table
     *
     * @return mixed
     */
    public function truncate()
    {
        return (bool) $this->connection->exec("truncate " . $this->tableName);
    }
    /**
     * Action insert
     *
     * @param array $values
     * 
     * @return int
     */
    public function insert($values)
    {
        $sql = "insert into " . $this->tableName . " set ";
        $values = Security::sanitaze($values, true);

        $sql .= parent::rangeField(parent::add2points(array_keys($values)));

        $stmt = $this->connection->prepare($sql);
        $this->bind($stmt, $values);
        $stmt->execute();

        return (int) $stmt->rowCount();
    }

    /**
     * Action insertAndGetLastId lance les actions insert et lastInsertId
     *
     * @param array $values
     * 
     * @return int
     */
    public function insertAndGetLastId(array $values)
    {
        $this->insert($values);
        return $this->connection->lastInsertId();
    }

    /**
     * Action first, récupère le première enregistrement
     *
     * @return mixed
     */
    public function first()
    {
        return $this->take(1)->get();
    }

    /**
     * Action first, récupère le première enregistrement
     *
     * @return mixed
     */
    public function last()
    {
        $c = $this->count();
        return $this->jump($c - 1)->take(1)->get();
    }

    /**
     * Action drop, supprime la table
     *
     * @return mixed
     */
    public function drop()
    {
        return (bool) $this->connection->exec("drop table " . $this->tableName);
    }

    /**
     * Utilitaire isComporaisonOperator, permet valider un opérateur
     *
     * @param $comp
     * 
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
     * paginate 
     * 
     * @param integer $n nombre d'element a récupérer
     * @param integer $current la page courrant
     * @param integer $chunk le nombre de groupe que l'on veux faire
     * @return array|\StdClass
     */
    public function paginate($n, $current = 0, $chunk = null)
    {
        --$current;

        if ($current < 0) {
            $current = 0;
        } else {
            if ($current > 0) {
                $current *= $n;
            }
        }

        $data = $this->jump($current)->take($n)->get();

        // groupé les données
        if (is_int($chunk)) {
            $data = array_chunk($data, $chunk);
        }

        return $data;
    }

    /**
     * collectionify, retourne les données de la DB sous en instance de Collection
     *
     * @return Collection
     */
    public function toCollection()
    {
        $data = $this->get();
        $coll =  new Collection();

        foreach($data as $key => $value) {
            $coll->add($value);
        }

        return $coll;
    }
}