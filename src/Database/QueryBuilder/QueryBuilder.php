<?php
namespace Bow\Database\QueryBuilder;

use Bow\Support\Str;
use Bow\Support\Util;
use Bow\Database\SqlUnity;
use Bow\Security\Security;
use Bow\Support\Collection;
use Bow\Database\Util\DBUtility;
use Bow\Exception\QueryBuilderException;

/**
 * Class QueryBuilder
 *
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Database
 */
class QueryBuilder extends DBUtility implements \JsonSerializable
{
    /**
     * @var string
     */
    private $loadClassName;

    /**
     * @var string
     */
    private $primaryKey = 'id';

    /**
     * @var string
     */
    private $table;

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
    private $whereDataBinding = [];

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
     * @var QueryBuilder
     */
    protected static $instance;

    /**
     * @var \PDO
     */
    private $connection;

    /**
     * @var bool
     */
    private $getOne = false;

    /**
     * Contructeur
     *
     * @param string $table
     * @param string $loadClassName
     * @param string $primaryKey
     * @param $connection
     */
    public function __construct($table, $connection, $loadClassName = null, $primaryKey = 'id')
    {
        if ($loadClassName == null) {
            $this->loadClassName = static::class;
        } else {
            $this->loadClassName = $loadClassName;
        }

        $this->connection = $connection;
        $this->primaryKey = $primaryKey;
        $this->table = $table;
    }

    // Vérrou sur la methode magic __clone
    private function __clone() {}

    /**
     * select, ajout de champ à séléction.
     *
     * SELECT $column | SELECT column1, column2, ...
     *
     * @param array $select
     *
     * @return QueryBuilder
     */
    public function select(array $select = ['*'])
    {
        if (count($select) == 0) {
            return $this;
        }

        if (count($select) == 1 && $select[0] == '*') {
            $this->select = '*';
        } else {
            $this->select = '`' . implode('`, `', $select) . '`';
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
     * @throws QueryBuilderException
     *
     * @return QueryBuilder
     */
    public function where($column, $comp = '=', $value = null, $boolean = 'and')
    {
        if (! static::isComporaisonOperator($comp)) {
            $value = $comp;
            $comp = '=';
        }

        // Ajout de matcher sur id.
        if ($comp == '=' && $value === null) {
            $value = $column;
            $column = 'id';
        }

        if ($value === null) {
            throw new QueryBuilderException('Valeur de comparaison non définir', E_ERROR);
        }

        if (!in_array(Str::lower($boolean), ['and', 'or'])) {
            throw new QueryBuilderException('Le booléen '. $boolean . ' non accepté', E_ERROR);
        }

        $this->whereDataBinding[$column] = $value;

        if ($this->where == null) {
            $this->where = $column . ' ' . $comp . ' :' . $column;
        } else {
            $this->where .= ' ' . $boolean .' '. $column . ' '. $comp .' :'. $column;
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
     * @throws QueryBuilderException
     *
     * @return QueryBuilder
     */
    public function orWhere($column, $comp = '=', $value = null)
    {
        if (is_null($this->where)) {
            throw new QueryBuilderException('Cette fonction ne peut pas être utiliser sans un where avant.', E_ERROR);
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
     * @return QueryBuilder
     */
    public function whereNull($column, $boolean = 'and')
    {
        if (!is_null($this->where)) {
            $this->where = '`' . $column . '` is null';
        } else {
            $this->where = ' ' . $boolean .' `' . $column .'` is null';
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
     * @return QueryBuilder
     */
    public function whereNotNull($column, $boolean = 'and')
    {
        if (is_null($this->where)) {
            $this->where = '`'. $column . '` is not null';
        } else {
            $this->where .= ' ' . $boolean .' `' . $column .'` is not null';
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
     * @throws QueryBuilderException
     *
     * @return QueryBuilder
     */
    public function whereBetween($column, array $range, $boolean = 'and')
    {

        if (count($range) !== 2) {
            throw new QueryBuilderException('Le paramètre 2 ne doit pas être un QueryBuilderau vide.', E_ERROR);
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
                $this->where .= ' ' . $boolean . ' `' . $column. '` between ' . $between;
            }
        }

        return $this;
    }

    /**
     * WHERE column NOT BETWEEN '' AND ''
     *
     * @param $column
     * @param $range
     * @return QueryBuilder
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
     * @throws QueryBuilderException
     *
     * @return QueryBuilder
     */
    public function whereIn($column, array $range, $boolean = 'and')
    {
        if (count($range) > 2) {
            $range = array_slice($range, 0, 2);
        } else {

            if (count($range) == 0) {
                throw new QueryBuilderException('Le paramètre 2 ne doit pas être un QueryBuilderau vide.', E_ERROR);
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
     * @throws QueryBuilderException
     *
     * @return QueryBuilder
     */
    public function whereNotIn($column, array $range)
    {
        $this->whereIn($column, $range, 'not');

        return $this;
    }

    /**
     * clause join
     *
     * @param $QueryBuilder
     *
     * @return QueryBuilder
     */
    public function join($QueryBuilder)
    {
        if (is_null($this->join)) {
            $this->join = 'inner join `'.$QueryBuilder.'`';
        } else {
            $this->join .= ', `'.$QueryBuilder.'`';
        }

        return $this;
    }

    /**
     * clause left join
     *
     * @param $QueryBuilder
     *
     * @throws QueryBuilderException
     *
     * @return QueryBuilder
     */
    public function leftJoin($QueryBuilder)
    {
        if (is_null($this->join)) {
            $this->join = 'left join `'.$QueryBuilder.'`';
        } else {
            if (!preg_match('/^(inner|right)\sjoin\s.*/', $this->join)) {
                $this->join .= ', `'.$QueryBuilder.'`';
            } else {
                throw new QueryBuilderException('La clause inner join est dèja initalisé.', E_ERROR);
            }
        }

        return $this;
    }
    
    /**
     * Action first, récupère le première enregistrement
     *
     * @return mixed
     */
    public function last()
    {
        $where = $this->where;
        $whereData = $this->whereDataBinding;

        // On compte le tout.
        $c = $this->count();

        $this->where = $where;
        $this->whereDataBinding = $whereData;

        return $this->jump($c - 1)->take(1)->getOne();
    }

    /**
     * clause right join
     *
     * @param $QueryBuilder
     * @throws QueryBuilderException
     * @return QueryBuilder
     */
    public function rightJoin($QueryBuilder)
    {
        if (is_null($this->join)) {
            $this->join = 'right join `'.$QueryBuilder.'`';
        } else {
            if (!preg_match('/^(inner|left)\sjoin\s.*/', $this->join)) {
                $this->join .= ', `'.$QueryBuilder.'`';
            } else {
                throw new QueryBuilderException('La clause inner join est dèja initialisé.', E_ERROR);
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
     * @throws QueryBuilderException
     *
     * @return QueryBuilder
     */
    public function on($column1, $comp = '=', $column2)
    {
        if (is_null($this->join)) {
            throw new QueryBuilderException('La clause inner join est dèja initialisé.', E_ERROR);
        }

        if (!$this->isComporaisonOperator($comp)) {
            $column2 = $comp;
        }

        if (!preg_match('/on/i', $this->join)) {
            $this->join .= ' on `' . $column1 . '` ' . $comp . ' `' . $column2 . '`';
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
     * @throws QueryBuilderException
     *
     * @return QueryBuilder
     */
    public function orOn($column, $comp = '=', $value)
    {
        if (is_null($this->join)) {
            throw new QueryBuilderException('La clause inner join est dèja initialisé.', E_ERROR);
        }

        if (!$this->isComporaisonOperator($comp)) {
            $value = $comp;
        }

        if (preg_match('/on/i', $this->join)) {
            $this->join .= ' or `'.$column.'` '.$comp.' '.$value;
        } else {
            throw new QueryBuilderException('La clause <b>on</b> n\'est pas initialisé.', E_ERROR);
        }

        return $this;
    }

    /**
     * clause group by
     *
     * @param string $column
     *
     * @return QueryBuilder
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
     * @return QueryBuilder
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
     * @return QueryBuilder
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
     * @return QueryBuilder
     */
    public function take($limit)
    {
        if (is_null($this->limit)) {
            $this->limit = (int) $limit;
            return $this;
        }

        if (preg_match('/^([\d]+),\s$/', $this->limit, $match)) {
            $this->limit = end($match).', '.$limit;
        }

        return $this;
    }

    /**
     * Max
     *
     * @param string $column
     *
     * @return QueryBuilder|number|array
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
     * @return QueryBuilder|number|array
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
     * @return QueryBuilder|number|array
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
     * @return QueryBuilder|number|array
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
        $sql = 'select ' . $aggregat . '(`' . $column . '`) from `' . $this->table . '`';

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

        if ($s->rowCount() > 1) {
            return $s->fetchAll();
        }

        return (int) $s->fetchColumn();
    }

    /**
     * Action get, seulement sur la requete de type select
     * @param array $columns
     * @return Collection|SqlUnity Si le mode de séléction unitaire n'est pas active
     */
    public function get(array $columns = [])
    {
        if (count($columns) > 0) {
            $this->select($columns);
        }

        // Execution de requete.
        $stmt = $this->connection->prepare($this->getSelectStatement());
        static::bind($stmt, $this->whereDataBinding);

        $this->whereDataBinding = [];
        $stmt->execute();

        $data = Security::sanitaze($stmt->fetchAll());
        $stmt->closeCursor();

        if ($this->loadClassName) {
            $loadClassName = $this->loadClassName;
        } else {
            $loadClassName = static::class;
        }

        if ($this->getOne) {
            $current = current($data);
            $this->getOne = false;

            if ($loadClassName !== QueryBuilder::class) {
                return new $loadClassName((array) $current);
            }

            $id = $this->primaryKey;
            $id_value = null;
            if (isset($current->{$id})) {
                $id_value = $current->{$id};
                unset($current->{$id});
            }
            return new SqlUnity($this, $id_value, $current);
        }

        foreach ($data as $key => $value) {
            if ($loadClassName !== QueryBuilder::class) {
                $data[$key] = new $loadClassName((array) $value);
                continue;
            }

            $id = $this->primaryKey;
            $id_value = null;

            if (isset($value->{$id})) {
                $id_value = $value->{$id};
                unset($value->{$id});
            }

            $data[$key] = new SqlUnity($this, $id_value, $value);
        }

        return new Collection($data);
    }

    /**
     * Permet de retourner un élément dans la liste de résultat
     *
     * @return SqlUnity|Collection|null
     */
    public function getOne()
    {
        $this->getOne = true;
        return $this->get();
    }

    /**
     * Demarrer un transaction dans la base de donnée.
     *
     * @param callable $cb
     * @return QueryBuilder
     */
    public function transition(callable $cb)
    {
        $where = $this->where;
        $data = $this->get();
        if (call_user_func_array($cb, [$data]) === true) {
            $this->where = $where;
        }

        return $this;
    }

    /**
     * count
     *
     * @param string $column          La colonne sur laquelle sera faite le `count`
     * @param callable $cb [optional] La fonction de rappel. Dans ou elle est définie
     *                                elle récupère en paramètre une instance de DatabaseErrorHanlder
     *                                et les données récupérés par la réquête.
     *
     * @return int
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

        $sql = 'select count(' . $column . ') from `' . $this->table .'`';

        if ($this->where !== null) {
            $sql .= ' where ' . $this->where;
            $this->where = null;
        }

        $stmt = $this->connection->prepare($sql);
        static::bind($stmt, $this->whereDataBinding);

        $this->whereDataBinding = [];
        $stmt->execute();

        $r = $stmt->fetchColumn();

        if (is_callable($cb)) {
            call_user_func_array($cb, [$r]);
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
        $sql = 'update `' . $this->table . '` set ';
        $sql .= Util::rangeField(Util::add2points(array_keys($data)));

        if (!is_null($this->where)) {
            $sql .= ' where ' . $this->where;
            $this->where = null;
            $data = array_merge($data, $this->whereDataBinding);
            $this->whereDataBinding = [];
        }

        $stmt = $this->connection->prepare($sql);
        $data = Security::sanitaze($data, true);
        static::bind($stmt, $data);

        // execution de la requête
        $stmt->execute();
        $r = $stmt->rowCount();

        if (is_callable($cb)) {
            return call_user_func_array($cb, [$r]);
        }

        return (int) $r;
    }

    /**
     * Action delete
     *
     * @param callable $cb
     *
     * @return int
     */
    public function delete(Callable $cb = null)
    {
        $sql = 'delete from `' . $this->table . '`';

        if (!is_null($this->where)) {
            $sql .= ' where ' . $this->where;
            $this->where = null;
        }

        $stmt = $this->connection->prepare($sql);

        static::bind($stmt, $this->whereDataBinding);
        $this->whereDataBinding = [];
        $stmt->execute();

        $r = $stmt->rowCount();

        if (is_callable($cb)) {
            return call_user_func_array($cb, [$r]);
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
     * @return int
     *
     * @throws QueryBuilderException
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
     * @return int
     */
    public function increment($column, $step = 1)
    {
        return $this->incrementAction($column, $step, '+');
    }


    /**
     * Action decrement, soustrait 1 par defaut sur le champs spécifié
     *
     * @param string $column
     * @param int $step
     *
     * @return int
     */
    public function decrement($column, $step = 1)
    {
        return $this->incrementAction($column, $step, '-');
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
    private function incrementAction($column, $step = 1, $sign = '')
    {
        $sql = 'update `' . $this->table . '` set `'.$column.'` = `'.$column.'` '.$sign.' '.$step;

        if (!is_null($this->where)) {
            $sql .= ' ' . $this->where;
            $this->where = null;
        }

        $stmt = $this->connection->prepare($sql);
        $this->bind($stmt, $this->whereDataBinding);
        $stmt->execute();

        return (int) $stmt->rowCount();
    }

    /**
     * Action truncate, vide la QueryBuilder
     *
     * @return mixed
     */
    public function truncate()
    {
        return (bool) $this->connection->exec('truncate `' . $this->table . '`;');
    }

    /**
     * Action insert
     *
     * @param array $values Les données a inserer dans la base de donnée.
     *
     * @return int
     */
    public function insert(array $values)
    {
        $nInserted = 0;
        $resets = [];

        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $nInserted += $this->insertOne($value);
            } else {
                $resets[$key] = $value;
            }
            unset($values[$key]);
        }

        if (! empty($resets)) {
            $nInserted += $this->insertOne($resets);
        }

        return $nInserted;
    }

    /**
     * @see insert
     * @param array $value
     * @return int
     */
    private function insertOne(array $value)
    {
        $fields = array_keys($value);

        $sql = 'insert into `' . $this->table . '` values (';
        $sql .= implode(', ', Util::add2points($fields, true));
        $sql .= ');';

        $stmt = $this->connection->prepare($sql);
        $this->bind($stmt, $value);

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
        $n = $this->connection->lastInsertId();

        return $n;
    }

    /**
     * Action drop, supprime la QueryBuilder
     *
     * @return mixed
     */
    public function drop()
    {
        return (bool) $this->connection->exec('drop table ' . $this->table);
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
     * @return Collection
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
        $dataBind = $this->whereDataBinding;
        $data = $this->jump($jump)->take($n)->get();

        // reinitialisation du where
        $this->where = $where;
        $this->whereDataBinding = $dataBind;

        // On compte le nombre de page qui reste
        $restOfPage = ceil($this->count() / $n) - $current;

        // groupé les données
        if (is_int($chunk)) {
            $data = array_chunk($data->toArray(), $chunk);
        }

        // active la pagination automatique.
        $data = [
            'next' => $current >= 1 && $restOfPage > 0 ? $current + 1 : false,
            'previous' => ($current - 1) <= 0 ? 1 : ($current - 1),
            'current' => $current,
            'data' => $data
        ];

        return new Collection($data);
    }

    /**
     * vérifie si un valeur existe déjà dans la DB
     *
     * @param string $column Le nom de la colonne a vérifié
     * @param mixed $value Le valeur de la colonne
     * @return bool
     * @throws QueryBuilderException
     */
    public function exists($column, $value = null)
    {
        if ($value == null) {
            $value = $column;
            $column = $this->primaryKey;
        }
        return $this->where($column, $value)->count() > 0 ? true : false;
    }

    /**
     * rétourne l'id de la dernière insertion
     *
     * @param string $name [optional]
     * @return string
     */
    public function getLastInsertId($name = null)
    {
        return $this->connection->lastInsertId($name);
    }

    /**
     * @return Collection
     */
    public function jsonSerialize()
    {
        return $this->get()->toJson();
    }

    /**
     * @param int $option
     * @return string
     */
    public function toJson($option = 0)
    {
        return json_encode($this->get()->values(), $option);
    }

    /**
     * Formate la requete select
     *
     * @return string
     */
    private function getSelectStatement()
    {
        $sql = 'select ';

        // Ajout de la clause select
        if (is_null($this->select)) {
            $sql .= '* from `' . $this->table .'`';
        } else {
            $sql .= $this->select . ' from `' . $this->table . '`';
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

        return $sql.';';
    }

    /**
     * __toString
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }
}