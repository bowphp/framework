<?php
namespace Bow\Database;

use Bow\Support\Str;
use Bow\Support\Security;
use Bow\Support\Collection;
use Bow\Exception\TableException;

/**
 * Class Table
 *
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Database
 */
class Table extends DatabaseTools implements \jsonSerializable
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
     * @var bool
     */
    private static $getOne = false;

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
     * @param \PDO $connection
     *
     * @return Table
     */
    public static function load($tableName, \PDO $connection)
    {
        if (self::$instance === null || self::$_tableName != $tableName) {
            self::$instance = new self($tableName, $connection);
        }

        return self::$instance;
    }

    /**
     * select, ajout de champ à séléction.
     *
     * SELECT $column | SELECT column1, column2, ...
     *
     * @param array $column
     *
     * @return $this
     */
    public function select($column)
    {

        if (is_array($column)) {
            $this->select = '`' . implode('`, `', $column) . '`';
            return $this;
        }

        if (is_string($column)) {
            $this->select = $column;
            return $this;
        }

        return $this;
    }

    /**
     * where, ajout condition de type where, si chainé ajout un <<and>>
     *
     * WHERE column1 $comp $value|column
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
    public function where($column, $comp = '=', $value = null, $boolean = 'and')
    {
        if (!static::isComporaisonOperator($comp)) {
            $value = $comp;
            $comp = '=';
        }

        // Ajout de matcher sur id.
        if ($comp == '=' && $value === null) {
            $value = $column;
            $column = 'id';
        }

        if ($value === null) {
            throw new TableException('valeur de comparaison non définir', E_ERROR);
        }

        if (!in_array(Str::lower($boolean), ['and', 'or'])) {
            throw new TableException('le booléen $boolean non accepté', E_ERROR);
        }

        $this->whereDataBind[$column] = $value;

        if ($this->where == null) {
            $this->where = $column . ' ' . $comp . ':' . $column;
        } else {
            $this->where .= $boolean .' '. $column . ' '. $comp .':'. $column;
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
    public function orWhere($column, $comp = '=', $value = null)
    {
        if (is_null($this->where)) {
            throw new TableException('Cette fonction ne peut pas être utiliser sans un where avant.', E_ERROR);
        }

        $this->where($column, $comp, $value, 'or');

        return $this;
    }

    /**
     * clause where avec comparaison en <<is null>>
     *
     * WHERE column IS NULL
     *
     * @param string $column
     * @param string $boolean='and'
     *
     * @return $this
     */
    public function whereNull($column, $boolean = 'and')
    {

        if (!is_null($this->where)) {
            $this->where = $column . 'is null';
        } else {
            $this->where = ' ' . $boolean .' ' . $column .' is null';
        }

        return $this;
    }

    /**
     * clause where avec comparaison en <<not null>>
     *
     * WHERE column NOT NULL
     *
     * @param $column
     * @param string $boolean='and|or'
     *
     * @return $this
     */
    public function whereNotNull($column, $boolean = 'and')
    {
        if (is_null($this->where)) {
            $this->where = $column . 'is not null';
        } else {
            $this->where .= ' ' . $boolean .' ' . $column .' is not null';
        }

        return $this;
    }

    /**
     * clause where avec comparaison en <<between>>
     *
     * WHERE column BETWEEN '' AND ''
     *
     * @param $column
     * @param array $range
     * @param string boolean='and|or'
     *
     * @throws TableException
     *
     * @return $this
     */
    public function whereBetween($column, array $range, $boolean = 'and')
    {

        if (count($range) !== 2) {
            throw new TableException('Le paramètre 2 ne doit pas être un tableau vide.', E_ERROR);
        }

        $between = implode(' and ', $range);

        if (is_null($this->where)) {
            if ($boolean == 'not') {
                $this->where = '`' . $column.'` not between ' . $between;
            } else {
                $this->where = '`' . $column . '` between ' . $between;
            }
        } else {
            if ($boolean == 'not') {
                $this->where .= ' and `'.$column .'`  not between ' . $between;
            } else {
                $this->where .= ' ' . $boolean . '`' . $column. '` between ' . $between;
            }
        }

        return $this;
    }

    /**
     * WHERE column NOT BETWEEN '' AND ''
     *
     * @param $column
     * @param $range
     * @return $this
     */
    public function whereNotBetween($column, array $range)
    {
        $this->whereBetween($column, $range, 'not');

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
    public function whereIn($column, array $range, $boolean = 'and')
    {
        if (count($range) > 2) {
            $range = array_slice($range, 0, 2);
        } else {

            if (count($range) == 0) {
                throw new TableException('Le paramètre 2 ne doit pas être un tableau vide.', E_ERROR);
            }

            $range = [$range[0], $range[0]];
        }

        $in = implode(', ', $range);

        if (is_null($this->where)) {
            if ($boolean == 'not') {
                $this->where = '`' . $column . '` not in ('.$in.')';
            } else {
                $this->where = '`' . $column .'` in ('.$in.')';
            }
        } else {
            if ($boolean == 'not') {
                $this->where .= ' and `' . $column . '` not in ('.$in.')';
            } else {
                $this->where .= ' and `'.$column.'` in ('.$in.')';
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
        $this->whereIn($column, $range, 'not');

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
            $this->join = 'inner join `'.$table.'`';
        } else {
            $this->join .= ',`'.$table.'`';
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
            $this->join = 'left join `'.$table.'`';
        } else {
            if (!preg_match('/^(inner|right)\sjoin\s.*/', $this->join)) {
                $this->join .= ', `'.$table.'`';
            } else {
                throw new TableException('La clause inner join est dèja initalisé.', E_ERROR);
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
            $this->join = 'right join `'.$table.'`';
        } else {
            if (!preg_match('/^(inner|left)\sjoin\s.*/', $this->join)) {
                $this->join .= ', `'.$table.'`';
            } else {
                throw new TableException('La clause inner join est dèja initialisé.', E_ERROR);
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
    public function on($column1, $comp = '=', $column2)
    {
        if (is_null($this->join)) {
            throw new TableException('La clause inner join est dèja initialisé.', E_ERROR);
        }

        if (!$this->isComporaisonOperator($comp)) {
            $column2 = $comp;
        }

        if (!preg_match('/on/i', $this->join)) {
            $this->join .= ' on `'.$column1.'` '.$comp.' '.$column2;
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
    public function orOn($column, $comp = '=', $value)
    {
        if (is_null($this->join)) {
            throw new TableException('La clause inner join est dèja initialisé.', E_ERROR);
        }

        if (!$this->isComporaisonOperator($comp)) {
            $value = $comp;
        }

        if (preg_match('/on/i', $this->join)) {
            $this->join .= ' or `'.$column.'` '.$comp.' '.$value;
        } else {
            throw new TableException('La clause <b>on</b> n\'est pas initialisé.', E_ERROR);
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
            $this->group = $column;
        }

        return $this;
    }

    /**
     * clause having, s'utilise avec un groupBy
     *
     * @param string $column
     * @param string $comp
     * @param null $value
     * @param string $boolean
     */
    public function having($column, $comp = '=', $value = null, $boolean = 'and')
    {
        if (!$this->isComporaisonOperator($comp)) {
            $value = $comp;
            $comp = '=';
        }
        if (is_null($this->havin)) {
            $this->havin = '`'.$column.'` '.$comp.' '.$value;
        } else {
            $this->havin .= ' '.$boolean.' `'.$column.'` '.$comp.' '.$value;
        }
    }

    /**
     * clause order by
     *
     * @param string $column
     * @param string $type
     *
     * @return $this
     */
    public function orderBy($column, $type = 'asc')
    {
        if (is_null($this->order)) {
            if (!in_array($type, ['asc', 'desc'])) {
                $type = 'asc';
            }

            $this->order = 'order by `'.$column.'` '.$type;
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
            $this->limit = $offset.', ';
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
            if (preg_match('/^([\d]+),$/', $this->limit, $match)) {
                array_shift($match);
                $this->limit = $match[0].', '.$limit;
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
        return $this->executeAgregat('max', $column);
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
        return $this->executeAgregat('min', $column);
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
        return $this->executeAgregat('avg', $column);
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
        return $this->executeAgregat('sum', $column);
    }

    /**
     * Lance en interne les requêtes utilisants les aggregats.
     *
     * @param $aggregat
     * @param string $column
     *
     * @return array|int
     */
    private function executeAgregat($aggregat, $column)
    {
        $sql = 'select ' . $aggregat . '(`' . $column . '`) from ' . $this->tableName;

        if (!is_null($this->where)) {
            $sql .= ' where ' . $this->where;
            $this->where = null;
        }

        if (!is_null($this->group)) {
            $sql .= ' ' . $this->group;
            $this->group = null;

            if (!isNull($this->havin)){
                $sql .= ' having ' . $this->havin;
            }
        }

        $s = $this->connection->prepare($sql);
        $s->execute();
        self::$errorInfo = $s->errorInfo();

        if ($s->rowCount() > 1) {
            return $s->fetchAll();
        }

        return (int) $s->fetchColumn();
    }

    // Actionner
    /**
     * Action get, seulement sur la requete de type select
     *
     * @param callable $cb
     *
     * @return array Si le mode de séléction unitaire n'est pas active
     */
    public function get($cb = null)
    {
        $sql = 'select ';

        // Ajout de la clause select
        if (is_null($this->select)) {
            $sql .= '* from `' . $this->tableName .'`';
        } else {
            $sql .= $this->select . ' from `' . $this->tableName . '`';
            $this->select = null;
        }

        // Ajout de la clause join
        if (!is_null($this->join)) {
            $sql .= ' join ' . $this->join;
            $this->join = null;
        }

        // Ajout de la clause where
        if (!is_null($this->where)) {
            $sql .= ' where ' . $this->where;
            $this->where = null;
        }

        // Ajout de la clause order
        if (!is_null($this->order)) {
            $sql .= ' ' . $this->order;
            $this->order = null;
        }

        // Ajout de la clause limit
        if (!is_null($this->limit)) {
            $sql .= ' limit ' . $this->limit;
            $this->limit = null;
        }

        // Ajout de la clause group
        if (!is_null($this->group)) {
            $sql .= ' group by ' . $this->group;
            $this->group = null;

            if (!is_null($this->havin)) {
                $sql .= ' having ' . $this->havin;
            }
        }

        // execution de requete.
        $stmt = $this->connection->prepare($sql);
        static::bind($stmt, $this->whereDataBind);
        $this->whereDataBind = [];
        $stmt->execute();

        // On récupère la dernière erreur.
        self::$errorInfo = $stmt->errorInfo();

        $data = Security::sanitaze($stmt->fetchAll());

        if (static::$getOne) {
            $data = current($data);
            static::$getOne = false;
        }

        $stmt->closeCursor();

        if (is_callable($cb)) {
            return call_user_func_array($cb, [$this->getLastError(), $data]);
        }

        return $data;
    }

    /**
     * Rétourne tout les enregistrements
     *
     * @param array $columns
     * @return array
     */
    public function all($columns = [])
    {
        if (count($columns) > 0) {
            $this->select = '`' . implode('`, `', $columns) . '`';
        }

        return $this->get();
    }

    /**
     * Permet de retourner un élément dans la liste de résultat
     *
     * @return mixed
     */
    public function getOne()
    {
        static::$getOne = true;
        return $this->get();
    }

    /**
     *
     *
     * @param callable $cb
     * @return $this
     */
    public function transition(Callable $cb)
    {
        $where = $this->where;
        $data = $this->get();
        $bool = call_user_func_array($cb, [$this->getLastError(), $data]);

        if ($bool === true) {
            $this->where = $where;
        }

        return $this;
    }

    /**
     * find
     *
     * @param mixed $id
     * @return SqlUnity
     * @throws TableException
     */
    public function find($id)
    {
        $this->where('id', $id);
        return new SqlUnity($this, $id);
    }

    /**
     * Récuper des informations sur la table ensuite les supprimes dans celle-ci
     *
     * @param Callable $cb La fonction de rappel qui si definir vous offre en parametre
     *                     Les données récupés et le nombre d'élément supprimé.
     *
     * @return array
     */
    public function findAndDelete($cb = null)
    {
        $where = $this->where;
        $whereData = $this->whereDataBind;

        // Exection de la réquete de récupération
        $data = $this->get();

        $this->where = $where;
        $this->whereDataBind = $whereData;

        // Exection de la réquete de suppréssion
        $n = $this->delete();

        if (is_callable($cb)) {
            return call_user_func_array($cb, [$n, $data]);
        }

        if (count($data) == 1) {
            $data = end($data);
        }

        return $data;
    }

    /**
     * Lance une execption en case de donnée non trouvé
     *
     * @param int|string $id
     * @return array
     *
     * @throws TableException
     */
    public function findOrFail($id)
    {
        $this->where('id', $id);
        $data = $this->get();

        if (count($data) == 0) {
            throw new TableException('Aucune donnée trouver.', E_WARNING);
        }

        return new SqlUnity($this, $id, $data[0]);
    }

    /**
     * Lance une execption en case de donnée non trouvé
     *
     * @param int|string $id
     * @return \stdClass
     *
     * @throws TableException
     */
    public function findOneOrFail($id = null)
    {
        static::$getOne = true;
        return $this->findOrFail($id);
    }

    /**
     * @param array $arr
     * @param Callable $callback
     *
     * @return array
     */
    public function findAndModify(array $arr, $callback = null)
    {
        // Transfert du where pour un prochaine définition
        // Dans le but de ne pas doubler le where
        $where = $this->where;
        $whereData = $this->whereDataBind;

        // Execution de GET, retourne un table comme nous avons pas definie de callback
        $data = $this->get();

        // On Effecture le transfert après éxécution du get
        $this->where = $where;
        $this->whereDataBind = $whereData;

        // nombre d'élément affécter lors de l'insertion.
        $n = $this->update($arr);

        if (is_callable($callback)) {
            return call_user_func_array($callback, [$this->getLastError(), $data, $n]);
        }

        return $data;
    }

    /**
     * @param array $arr Les nouvelles informations à inserer dans la base de donnée
     * @param callable $callback [optional] La fonction de rappel. Dans ou elle est définie
     *                                      elle récupère en paramètre une instance de DatabaseErrorHanlder
     *                                      et les données récupérés par la réquête.
     *
     * @return array
     */
    public function findOneAndModify(array $arr, Callable $callback = null)
    {
        static::$getOne = true;
        return $this->findAndModify($arr, $callback);
    }

    /**
     * count
     *
     * @param string $column          La colonne sur laquelle sera faite le `count`
     * @param callable $cb [optional] La fonction de rappel. Dans ou elle est définie
     *                                elle récupère en paramètre une instance de DatabaseErrorHanlder
     *                                et les données récupérés par la réquête.
     *
     * @return DatabaseErrorHandler
     */
    public function count($column = '*', Callable $cb = null)
    {
        if (is_callable($column)) {
            $cb = $column;
            $column = '*';
        }

        if ($column != '*') {
            $column = '`' . $column . '`';
        }

        $sql = 'select count(' . $column . ') from `' . $this->tableName .'`';

        if ($this->where !== null) {
            $sql .= ' where ' . $this->where;
            $this->where = null;
        }

        $stmt = $this->connection->prepare($sql);
        static::bind($stmt, $this->whereDataBind);

        $this->whereDataBind = [];
        $stmt->execute();

        self::$errorInfo = $stmt->errorInfo();
        $r = $stmt->fetchColumn();

        if (is_callable($cb)) {
            call_user_func_array($cb, [$this->getResponseOfQuery($r), $r]);
        }

        return (int) $r;
    }

    /**
     * Action update
     *
     * @param array $data  Les données à mettre à jour
     * @param callable $cb La fonction de rappel. Dans ou elle est définie
     *                     elle récupère en paramètre une instance de DatabaseErrorHanlder
     *                     et les données récupérés par la réquête.
     *
     * @return int
     */
    public function update(array $data = [], Callable $cb = null)
    {
        $sql = 'update `' . $this->tableName . '` set ';
        $sql .= parent::rangeField(parent::add2points(array_keys($data)));

        if (!is_null($this->where)) {
            $sql .= ' where ' . $this->where;
            $this->where = null;
            $data = array_merge($data, $this->whereDataBind);
            $this->whereDataBind = [];
        }

        $stmt = $this->connection->prepare($sql);
        $data = Security::sanitaze($data, true);
        static::bind($stmt, $data);

        // execution de la requête
        $stmt->execute();

        // récupération de la dernière erreur.
        self::$errorInfo = $stmt->errorInfo();

        $r = $stmt->rowCount();

        if (is_callable($cb)) {
            return call_user_func_array($cb, [$this->getResponseOfQuery($r), $r]);
        }

        return (int) $r;
    }

    /**
     * Action delete
     *
     * @param callable $cb
     *
     * @return DatabaseErrorHandler
     */
    public function delete(Callable $cb = null)
    {
        $sql = 'delete from `' . $this->tableName . '`';

        if (!is_null($this->where)) {
            $sql .= ' where ' . $this->where;
            $this->where = null;
        }

        $stmt = $this->connection->prepare($sql);

        static::bind($stmt, $this->whereDataBind);
        $this->whereDataBind = [];
        $stmt->execute();
        self::$errorInfo = $stmt->errorInfo();
        $r = $stmt->rowCount();

        if (is_callable($cb)) {
            return call_user_func_array($cb, [$this->getResponseOfQuery($r), $r]);
        }

        return (int) $r;
    }

    /**
     * remove alise simplifié de delete.
     *
     * @param string $column Le nom du champs de la conditions
     * @param string $comp Le type de comparaison
     * @param string $value [optinal] La valeur a comparé
     *
     * @return DatabaseErrorHandler
     *
     * @throws TableException
     */
    public function remove($column, $comp = '=', $value = null)
    {
        $this->where = null;
        return $this->where($column, $comp, $value)->delete();
    }

    /**
     * Action increment, ajout 1 par défaut sur le champs spécifié
     *
     * @param string $column La colonne sur laquel est faite incrémentation
     * @param int $step Le part de l'incrementation
     *
     * @return DatabaseErrorHandler
     */
    public function increment($column, $step = 1)
    {
        return $this->crement($column, $step, '+');
    }


    /**
     * Action decrement, soustrait 1 par defaut sur le champs spécifié
     *
     * @param string $column
     * @param int $step
     *
     * @return DatabaseErrorHandler
     */
    public function decrement($column, $step = 1)
    {
        return $this->crement($column, $step, '-');
    }

    /**
     * method permettant de customiser les methods increment et decrement
     *
     * @param string $column
     * @param int $step
     * @param string $sign
     *
     * @return DatabaseErrorHandler
     */
    private function crement($column, $step = 1, $sign = '')
    {
        $sql = 'update `' . $this->tableName . '` set `'.$column.'` = `'.$column.'` '.$sign.' '.$step;

        if (!is_null($this->where)) {
            $sql .= ' ' . $this->where;
            $this->where = null;
        }

        $stmt = $this->connection->prepare($sql);
        $this->bind($stmt, $this->whereDataBind);
        $stmt->execute();
        static::$errorInfo = $stmt->errorInfo();

        return $this->getResponseOfQuery((int) $stmt->rowCount());
    }

    /**
     * Action truncate, vide la table
     *
     * @return mixed
     */
    public function truncate()
    {
        return (bool) $this->connection->exec('truncate ' . $this->tableName);
    }

    /**
     * Action insert
     *
     * @param array $values Les données a inserer dans la base de donnée.
     *
     * @return DatabaseErrorHandler
     */
    public function insert(array $values)
    {
        $nInserted = 0;

        if (isset($values[0]) && is_array($values[0])) {
            foreach($values as $key => $data) {
                $nInserted += $this->insertOne($data);
            }
        } else {
            $nInserted = $this->insertOne($values);
        }

        return $this->getResponseOfQuery($nInserted);
    }

    /**
     * save aliase sur l'action insert
     *
     * @param array $values Les données a inserer dans la base de donnée.
     *
     * @return DatabaseErrorHandler
     */
    public function save(array $values)
    {
        return $this->insert($values);
    }

    /**
     * @see insert
     * @param array $value
     * @return int
     */
    private function insertOne(array $value)
    {
        $sql = 'insert into `' . $this->tableName . '` set ';

        $sql .= parent::rangeField(parent::add2points(array_keys($value)));
        $stmt = $this->connection->prepare($sql);

        $this->bind($stmt, $value);
        $stmt->execute();

        self::$errorInfo = $stmt->errorInfo();
        return (int) $stmt->rowCount();
    }

    /**
     * Action insertAndGetLastId lance les actions insert et lastInsertId
     *
     * @param array $values
     * @param bool $withInfo Si a true cette fonction retourn un object ['info' => DatabaseErrorHandler, 'id' => int]
     *
     * @return int|DatabaseErrorHandler
     */
    public function insertAndGetLastId(array $values, $withInfo = false)
    {
        $r = $this->insert($values);
        $n = $this->connection->lastInsertId();

        if ($withInfo) {
            $n = (object) ['info' => $r, 'id' => $n];
        }

        return $n;
    }

    /**
     * saveAndGetLastId aliase sur action insertAndGetLastId, lance les actions insert et lastInsertId
     *
     * @param array $values
     * @param bool $withInfo
     *
     * @return int|DatabaseErrorHandler
     */
    public function saveAndGetLastId(array $values, $withInfo = false)
    {
        return $this->insertAndGetLastId($values, $withInfo);
    }

    /**
     * Action first, récupère le première enregistrement
     *
     * @return mixed
     */
    public function first()
    {
        return $this->take(1)->getOne();
    }

    /**
     * Action first, récupère le première enregistrement
     *
     * @return mixed
     */
    public function last()
    {
        $where = $this->where;
        $whereData = $this->whereDataBind;

        // On compte le tout.
        $c = $this->count();

        $this->where = $where;
        $this->whereDataBind = $whereData;

        return $this->jump($c - 1)->take(1)->getOne();
    }

    /**
     * Action drop, supprime la table
     *
     * @return mixed
     */
    public function drop()
    {
        return (bool) $this->connection->exec('drop table ' . $this->tableName);
    }

    /**
     * Utilitaire isComporaisonOperator, permet valider un opérateur
     *
     * @param string $comp Le comparateur logique
     *
     * @return bool
     */
    private static function isComporaisonOperator($comp)
    {
        return in_array($comp, ['=', '>', '<', '>=', '=<', '<>', '!=', 'LIKE', 'like'], true);
    }

    /**
     * paginate
     *
     * @param integer $n nombre d'element a récupérer
     * @param integer $current la page courrant
     * @param integer $chunk le nombre l'élément par groupe que l'on veux faire.
     * @return \stdClass
     */
    public function paginate($n, $current = 0, $chunk = null)
    {
        // On vas une page en arrière
        --$current;

        // variable contenant le nombre de saut. $jump;

        if ($current <= 0) {
            $jump = 0;
            $current = 1;
        } else {
            $jump = $n * $current;
            $current++;
        }

        // sauvegarde des informations sur le where
        $where = $this->where;
        $dataBind = $this->whereDataBind;
        $data = $this->jump($jump)->take($n)->get();

        // reinitialisation du where
        $this->where = $where;
        $this->whereDataBind = $dataBind;

        // On compte le nombre de page qui reste
        $restOfPage = ceil($this->count() / $n) - $current;

        // groupé les données
        if (is_int($chunk)) {
            $data = array_chunk($data, $chunk);
        }

        // active la pagination automatique.
        $data = [
            'next' => $current >= 1 && $restOfPage > 0 ? $current + 1 : false,
            'previous' => ($current - 1) <= 0 ? 1 : ($current - 1),
            'current' => $current,
            'data' => $data,
            'info' => $this->getResponseOfQuery(count($data))
        ];

        return (object) $data;
    }

    /**
     * toCollection, retourne les données de la DB sous en instance de Collection
     *
     * @return Collection
     */
    public function toCollection()
    {
        $data = $this->get();
        $coll =  new Collection();

        if (count($data) == 1) {
            $data = current($data);
        }

        foreach($data as $key => $value) {
            $coll->add($key, $value);
        }

        return $coll;
    }

    /**
     * collectionify alias de toCollection.
     *
     * @return Collection
     */
    public function collectionify()
    {
        return $this->toCollection();
    }

    /**
     * vérifie si un valeur existe déjà dans la DB
     *
     * @param string $column Le nom de la colonne a vérifié
     * @param mixed $value Le valeur de la colonne
     * @return bool
     * @throws TableException
     */
    public function exists($column, $value = null)
    {
        if ($value == null) {
            $value = $column;
            $column = 'id';
        }
        return $this->where($column, $value)->count() > 0 ? true : false;
    }

    /**
     * rétourne l'id de la dernière insertion
     *
     * @param string $name [optional]
     * @return string
     */
    public function lastId($name = null)
    {
        return $this->connection->lastInsertId($name);
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->get();
    }

    /**
     * @param int $option
     * @return string
     */
    public function toJson($option = 0)
    {
        return json_encode($this->get(), $option);
    }

    /**
     * retourne les informations sur l'erreur PDO
     *
     * @return DatabaseErrorHandler
     */
    public function getLastError()
    {
        return new DatabaseErrorHandler(self::$errorInfo);
    }

    /**
     * Retourne une instance de DatabaseErrorHandler, dans lequel on peut avoir des informations sur
     * l'erreur de la réquête.
     *
     * @param int $n Le nombre de ligne affécter par un réquête.
     * @return DatabaseErrorHandler
     */
    private function getResponseOfQuery($n)
    {
        $r = $this->getLastError();
        $r->rowAffected = $n;
        return $r;
    }
}