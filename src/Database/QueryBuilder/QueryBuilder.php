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
    private static $classname;

    /**
     * @var string
     */
    protected static $primaryKey = 'id';

    /**
     * @var string
     */
    private static $table;

    /**
     * @var string
     */
    protected $select = null;

    /**
     * @var string
     */
    protected $where = null;

    /**
     * @var array
     */
    protected $whereDataBinding = [];

    /**
     * @var string
     */
    protected $join = null;

    /**
     * @var string
     */
    protected $limit = null;

    /**
     * @var string
     */
    protected $group = null;

    /**
     * @var string
     */
    protected $havin = null;

    /**
     * @var string
     */
    protected $order = null;

    /**
     * @var QueryBuilder
     */
    protected static $instance;

    /**
     * @var \PDO
     */
    private static $connection;

    /**
     * @var bool
     */
    protected static $getOne = false;

    /**
     * @var bool
     */
    protected static $timestmap = false;

    /**
     * Contructeur
     *
     * @param string $table
     * @param string $classname
     * @param $connection
     */
    protected function __construct($table, $connection, $classname = null)
    {
        self::$connection = $connection;
        static::$table = $table;
        if ($classname == null) {
            self::$classname = static::class;
        } else {
            self::$classname = $classname;
        }
    }

    // fonction magic __clone
    private function __clone() {}

    /**
     * Charge le singleton
     *
     * @param string $table
     * @param \PDO $connection
     * @param string $classname
     *
     * @return QueryBuilder
     */
    public static function make($table, \PDO $connection, $classname = null)
    {
        if (static::$instance === null || static::$table != $table) {
            static::$instance = new self($table, $connection, $classname);
        }
        return static::$instance;
    }

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
            return static::$instance;
        }

        if (count($select) == 1 && $select[0] == '*') {
            static::$instance->select = '*';
        } else {
            static::$instance->select = '`' . implode('`, `', $select) . '`';
        }

        return static::$instance;
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

        static::$instance->whereDataBinding[$column] = $value;

        if (static::$instance->where == null) {
            static::$instance->where = $column . ' ' . $comp . ' :' . $column;
        } else {
            static::$instance->where .= ' ' . $boolean .' '. $column . ' '. $comp .' :'. $column;
        }

        return static::$instance;
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
        if (is_null(static::$instance->where)) {
            throw new QueryBuilderException('Cette fonction ne peut pas être utiliser sans un where avant.', E_ERROR);
        }

        static::$instance->where($column, $comp, $value, 'or');

        return static::$instance;
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
        if (!is_null(static::$instance->where)) {
            static::$instance->where = '`' . $column . '` is null';
        } else {
            static::$instance->where = ' ' . $boolean .' `' . $column .'` is null';
        }

        return static::$instance;
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
        if (is_null(static::$instance->where)) {
            static::$instance->where = '`'. $column . '` is not null';
        } else {
            static::$instance->where .= ' ' . $boolean .' `' . $column .'` is not null';
        }

        return static::$instance;
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

        if (is_null(static::$instance->where)) {
            if ($boolean == 'not') {
                static::$instance->where = '`' . $column.'` not between ' . $between;
            } else {
                static::$instance->where = '`' . $column . '` between ' . $between;
            }
        } else {
            if ($boolean == 'not') {
                static::$instance->where .= ' and `'.$column .'`  not between ' . $between;
            } else {
                static::$instance->where .= ' ' . $boolean . ' `' . $column. '` between ' . $between;
            }
        }

        return static::$instance;
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
        static::$instance->whereBetween($column, $range, 'not');

        return static::$instance;
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

        if (is_null(static::$instance->where)) {
            if ($boolean == 'not') {
                static::$instance->where = '`' . $column . '` not in ('.$in.')';
            } else {
                static::$instance->where = '`' . $column .'` in ('.$in.')';
            }
        } else {
            if ($boolean == 'not') {
                static::$instance->where .= ' and `' . $column . '` not in ('.$in.')';
            } else {
                static::$instance->where .= ' and `'.$column.'` in ('.$in.')';
            }
        }

        return static::$instance;
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
        static::$instance->whereIn($column, $range, 'not');

        return static::$instance;
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
        if (is_null(static::$instance->join)) {
            static::$instance->join = 'inner join `'.$QueryBuilder.'`';
        } else {
            static::$instance->join .= ', `'.$QueryBuilder.'`';
        }

        return static::$instance;
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
        if (is_null(static::$instance->join)) {
            static::$instance->join = 'left join `'.$QueryBuilder.'`';
        } else {
            if (!preg_match('/^(inner|right)\sjoin\s.*/', static::$instance->join)) {
                static::$instance->join .= ', `'.$QueryBuilder.'`';
            } else {
                throw new QueryBuilderException('La clause inner join est dèja initalisé.', E_ERROR);
            }
        }

        return static::$instance;
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
        if (is_null(static::$instance->join)) {
            static::$instance->join = 'right join `'.$QueryBuilder.'`';
        } else {
            if (!preg_match('/^(inner|left)\sjoin\s.*/', static::$instance->join)) {
                static::$instance->join .= ', `'.$QueryBuilder.'`';
            } else {
                throw new QueryBuilderException('La clause inner join est dèja initialisé.', E_ERROR);
            }
        }
        return static::$instance;
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
        if (is_null(static::$instance->join)) {
            throw new QueryBuilderException('La clause inner join est dèja initialisé.', E_ERROR);
        }

        if (!static::$instance->isComporaisonOperator($comp)) {
            $column2 = $comp;
        }

        if (!preg_match('/on/i', static::$instance->join)) {
            static::$instance->join .= ' on `' . $column1 . '` ' . $comp . ' `' . $column2 . '`';
        }

        return static::$instance;
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
        if (is_null(static::$instance->join)) {
            throw new QueryBuilderException('La clause inner join est dèja initialisé.', E_ERROR);
        }

        if (!static::$instance->isComporaisonOperator($comp)) {
            $value = $comp;
        }

        if (preg_match('/on/i', static::$instance->join)) {
            static::$instance->join .= ' or `'.$column.'` '.$comp.' '.$value;
        } else {
            throw new QueryBuilderException('La clause <b>on</b> n\'est pas initialisé.', E_ERROR);
        }

        return static::$instance;
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
        if (is_null(static::$instance->group)) {
            static::$instance->group = $column;
        }

        return static::$instance;
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
        if (!static::$instance->isComporaisonOperator($comp)) {
            $value = $comp;
            $comp = '=';
        }
        if (is_null(static::$instance->havin)) {
            static::$instance->havin = '`'.$column.'` '.$comp.' '.$value;
        } else {
            static::$instance->havin .= ' '.$boolean.' `'.$column.'` '.$comp.' '.$value;
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
        if (is_null(static::$instance->order)) {
            if (!in_array($type, ['asc', 'desc'])) {
                $type = 'asc';
            }

            static::$instance->order = 'order by `'.$column.'` '.$type;
        }

        return static::$instance;
    }

    /**
     * jump = offset
     *
     * @param int $offset
     * @return QueryBuilder
     */
    public function jump($offset = 0)
    {
        if (is_null(static::$instance->limit)) {
            static::$instance->limit = $offset.', ';
        }

        return static::$instance;
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
        if (is_null(static::$instance->limit)) {
            static::$instance->limit = $limit;
            return static::$instance;
        }

        if (preg_match('/^([\d]+),$/', static::$instance->limit, $match)) {
            array_shift($match);
            static::$instance->limit = $match[0].', '.$limit;
        }

        return static::$instance;
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
        return static::$instance->executeAgregat('max', $column);
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
        return static::$instance->executeAgregat('min', $column);
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
        return static::$instance->executeAgregat('avg', $column);
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
        return static::$instance->executeAgregat('sum', $column);
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
        $sql = 'select ' . $aggregat . '(`' . $column . '`) from `' . static::$table . '`';

        if (!is_null(static::$instance->where)) {
            $sql .= ' where ' . static::$instance->where;
            static::$instance->where = null;
        }

        if (!is_null(static::$instance->group)) {
            $sql .= ' ' . static::$instance->group;
            static::$instance->group = null;

            if (!isNull(static::$instance->havin)){
                $sql .= ' having ' . static::$instance->havin;
            }
        }

        $s = self::$connection->prepare($sql);
        $s->execute();

        if ($s->rowCount() > 1) {
            return $s->fetchAll();
        }

        return (int) $s->fetchColumn();
    }

    /**
     * Action get, seulement sur la requete de type select
     *
     * @param callable $cb
     *
     * @return Collection|SqlUnity Si le mode de séléction unitaire n'est pas active
     */
    public function get($cb = null)
    {
        $sql = static::$instance->getSelectStatement();

        // execution de requete.
        $stmt = self::$connection->prepare($sql);
        static::bind($stmt, static::$instance->whereDataBinding);
        static::$instance->whereDataBinding = [];
        $stmt->execute();

        $data = Security::sanitaze($stmt->fetchAll());
        $stmt->closeCursor();

        if (self::$classname) {
            $classname = self::$classname;
        } else {
            $classname = static::class;
        }

        if (static::$getOne) {
            $current = current($data);
            static::$getOne = false;
            if (self::$classname === QueryBuilder::class) {
                $id = static::$primaryKey;
                $id_value = null;
                if (isset($current->{$id})) {
                    $id_value = $current->{$id};
                    unset($current->{$id});
                }
                return new SqlUnity(static::$instance, $id_value, $current);
            }
            return new $classname((array) $current);
        }

        if (is_callable($cb)) {
            return call_user_func_array($cb, [$data]);
        }

        foreach ($data as $key => $value) {
            if (self::$classname === QueryBuilder::class) {
                $id = static::$primaryKey;
                $id_value = null;
                if (isset($value->{$id})) {
                    $id_value = $value->{$id};
                    unset($value->{$id});
                }
                $data[$key] = new SqlUnity(static::$instance, $id_value, $value);
            } else {
                $data[$key] = new $classname((array) $value);
            }
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
        static::$getOne = true;
        return static::$instance->get();
    }

    /**
     * Demarrer un transaction dans la base de donnée.
     *
     * @param callable $cb
     * @return QueryBuilder
     */
    public function transition(callable $cb)
    {
        $where = static::$instance->where;
        $data = static::$instance->get();
        if (call_user_func_array($cb, [$data]) === true) {
            static::$instance->where = $where;
        }

        return static::$instance;
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

        $sql = 'select count(' . $column . ') from `' . static::$table .'`';

        if (static::$instance->where !== null) {
            $sql .= ' where ' . static::$instance->where;
            static::$instance->where = null;
        }

        $stmt = self::$connection->prepare($sql);
        static::bind($stmt, static::$instance->whereDataBinding);

        static::$instance->whereDataBinding = [];
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
        $sql = 'update `' . static::$table . '` set ';
        $sql .= Util::rangeField(Util::add2points(array_keys($data)));

        if (!is_null(static::$instance->where)) {
            $sql .= ' where ' . static::$instance->where;
            static::$instance->where = null;
            $data = array_merge($data, static::$instance->whereDataBinding);
            static::$instance->whereDataBinding = [];
        }

        $stmt = self::$connection->prepare($sql);
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
        $sql = 'delete from `' . static::$table . '`';

        if (!is_null(static::$instance->where)) {
            $sql .= ' where ' . static::$instance->where;
            static::$instance->where = null;
        }

        $stmt = self::$connection->prepare($sql);

        static::bind($stmt, static::$instance->whereDataBinding);
        static::$instance->whereDataBinding = [];
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
        static::$instance->where = null;
        return static::$instance->where($column, $comp, $value)->delete();
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
        return static::$instance->incrementAction($column, $step, '+');
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
        return static::$instance->incrementAction($column, $step, '-');
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
        $sql = 'update `' . static::$table . '` set `'.$column.'` = `'.$column.'` '.$sign.' '.$step;

        if (!is_null(static::$instance->where)) {
            $sql .= ' ' . static::$instance->where;
            static::$instance->where = null;
        }

        $stmt = self::$connection->prepare($sql);
        static::$instance->bind($stmt, static::$instance->whereDataBinding);
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
        return (bool) self::$connection->exec('truncate `' . static::$table . '`;');
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
                $nInserted += static::$instance->insertOne($value);
            } else {
                $resets[$key] = $value;
            }
            unset($values[$key]);
        }

        if (! empty($resets)) {
            $nInserted += static::$instance->insertOne($resets);
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

        $sql = 'insert into `' . static::$table . '` values (';
        $sql .= implode(', ', Util::add2points($fields, true));
        $sql .= ');';

        $stmt = self::$connection->prepare($sql);
        static::$instance->bind($stmt, $value);

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
        static::$instance->insert($values);
        $n = self::$connection->lastInsertId();

        return $n;
    }

    /**
     * Action drop, supprime la QueryBuilder
     *
     * @return mixed
     */
    public function drop()
    {
        return (bool) self::$connection->exec('drop table ' . static::$table);
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
        $where = static::$instance->where;
        $dataBind = static::$instance->whereDataBinding;
        $data = static::$instance->jump($jump)->take($n)->get();

        // reinitialisation du where
        static::$instance->where = $where;
        static::$instance->whereDataBinding = $dataBind;

        // On compte le nombre de page qui reste
        $restOfPage = ceil(static::$instance->count() / $n) - $current;

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

        return (object) $data;
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
            $column = static::$primaryKey;
        }
        return static::$instance->where($column, $value)->count() > 0 ? true : false;
    }

    /**
     * rétourne l'id de la dernière insertion
     *
     * @param string $name [optional]
     * @return string
     */
    public function getLastInsertId($name = null)
    {
        return self::$connection->lastInsertId($name);
    }

    /**
     * @return Collection
     */
    public function jsonSerialize()
    {
        return static::$instance->get()->toJson();
    }

    /**
     * @param int $option
     * @return string
     */
    public function toJson($option = 0)
    {
        return json_encode(static::$instance->get()->values(), $option);
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
        if (is_null(static::$instance->select)) {
            $sql .= '* from `' . static::$table .'`';
        } else {
            $sql .= static::$instance->select . ' from `' . static::$table . '`';
            static::$instance->select = null;
        }

        // Ajout de la clause join
        if (!is_null(static::$instance->join)) {
            $sql .= ' join ' . static::$instance->join;
            static::$instance->join = null;
        }

        // Ajout de la clause where
        if (!is_null(static::$instance->where)) {
            $sql .= ' where ' . static::$instance->where;
            static::$instance->where = null;
        }

        // Ajout de la clause order
        if (!is_null(static::$instance->order)) {
            $sql .= ' ' . static::$instance->order;
            static::$instance->order = null;
        }

        // Ajout de la clause limit
        if (!is_null(static::$instance->limit)) {
            $sql .= ' limit ' . static::$instance->limit;
            static::$instance->limit = null;
        }

        // Ajout de la clause group
        if (!is_null(static::$instance->group)) {
            $sql .= ' group by ' . static::$instance->group;
            static::$instance->group = null;

            if (!is_null(static::$instance->havin)) {
                $sql .= ' having ' . static::$instance->havin;
            }
        }

        return $sql;
    }

    /**
     * __toString
     *
     * @return string
     */
    public function __toString()
    {
        return static::$instance->toJson();
    }
}