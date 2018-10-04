<?php

namespace Bow\Database;

use Bow\Database\Exception\QueryBuilderException;
use Bow\Security\Sanitize;
use Bow\Support\Str;
use Bow\Support\Util;
use PDO;
use stdClass;

/**
 * Class Builder
 *
 * @author  Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Database
 */
class QueryBuilder extends Tool implements \JsonSerializable
{
    /**
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * @var string
     */
    protected $table;

    /**
     * @var string
     */
    protected $select;

    /**
     * @var string
     */
    protected $where;

    /**
     * @var array
     */
    protected $whereDataBinding = [];

    /**
     * @var string
     */
    protected $join;

    /**
     * @var string
     */
    protected $limit;

    /**
     * @var string
     */
    protected $group;

    /**
     * @var string
     */
    protected $havin;

    /**
     * @var string
     */
    protected $order;

    /**
     * @var \PDO
     */
    protected $connection;

    /**
     * @var bool
     */
    protected $first = false;

    /**
     * @var string
     */
    protected $prefix = '';

    /**
     * Contructeur
     *
     * @param string $table
     * @param PDO $connection
     */
    public function __construct($table, PDO $connection)
    {
        $this->connection = $connection;

        $this->table = $table;
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
     * @param null    $value
     * @param $boolean
     *
     * @throws QueryBuilderException
     *
     * @return QueryBuilder
     */
    public function where($column, $comp = '=', $value = null, $boolean = 'and')
    {
        if (!static::isComporaisonOperator($comp) || is_null($value)) {
            $value = $comp;

            $comp = '=';
        }

        if ($value === null) {
            throw new QueryBuilderException('Valeur de comparaison non définie', E_ERROR);
        }

        if (!in_array(Str::lower($boolean), ['and', 'or'])) {
            throw new QueryBuilderException('Le booléen '. $boolean . ' non accepté', E_ERROR);
        }

        $this->whereDataBinding[$column] = $value;

        if ($this->where == null) {
            $this->where = '('. $column . ' ' . $comp . ' :' . $column . ')';
        } else {
            $this->where .= ' ' . $boolean . ' ('. $column . ' '. $comp .' :'. $column. ')';
        }

        return $this;
    }

    /**
     * orWhere, retourne une condition de type [where colonne = value <<or colonne = value>>]
     *
     * @param string $column
     * @param string $comp
     * @param null   $value
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

        return $this->where($column, $comp, $value, 'or');
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
        if (is_null($this->where)) {
            $this->where = '(`' . $column . '` is null)';
        } else {
            $this->where .= ' ' . $boolean .' (`' . $column .'` is null)';
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
            $this->where = '(`'. $column . '` is not null)';
        } else {
            $this->where .= ' ' . $boolean .' (`' . $column .'` is not null)';
        }

        return $this;
    }

    /**
     * clause where avec comparaison en <<between>>
     *
     * WHERE column BETWEEN '' AND ''
     *
     * @param $column
     * @param array                   $range
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
                $this->where = '(`' . $column.'` not between ' . $between . ')';
            } else {
                $this->where = '(`' . $column . '` between ' . $between . ')';
            }
        } else {
            if ($boolean == 'not') {
                $this->where .= ' and (`'.$column .'` not between ' . $between . ')';
            } else {
                $this->where .= ' ' . $boolean . ' (`' . $column. '` between ' . $between . ')';
            }
        }

        return $this;
    }

    /**
     * WHERE column NOT BETWEEN '' AND ''
     *
     * @param  $column
     * @param  $range
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
     * @param array  $range
     * @param string $boolean
     *
     * @throws QueryBuilderException
     *
     * @return QueryBuilder
     */
    public function whereIn($column, array $range, $boolean = 'and')
    {
        if (count($range) == 0) {
            throw new QueryBuilderException('Le paramètre 2 ne doit pas être un QueryBuilderau vide.', E_ERROR);
        }

        $map = array_map(
            function () {
                return '?';
            },
            $range
        );

        $this->whereDataBinding = array_merge($range, $this->whereDataBinding);

        $in = implode(', ', $map);

        if (is_null($this->where)) {
            if ($boolean == 'not') {
                $this->where = '(`' . $column . '` not in ('.$in.'))';
            } else {
                $this->where = '(`' . $column .'` in ('.$in.'))';
            }
        } else {
            if ($boolean == 'not') {
                $this->where .= ' and (`' . $column . '` not in ('.$in.'))';
            } else {
                $this->where .= ' and (`'.$column.'` in ('.$in.'))';
            }
        }

        return $this;
    }

    /**
     * clause where avec comparaison en <<not in>>
     *
     * @param string $column
     * @param array  $range
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
     * @param string   $table
     * @param callable $callabe
     *
     * @return QueryBuilder
     */
    public function join($table, callable $callabe = null)
    {
        $table = $this->getPrefix().$table;

        if (is_null($this->join)) {
            $this->join = 'inner join `'.$table.'`';
        } else {
            $this->join .= ', `'.$table.'`';
        }

        if (is_callable($callabe)) {
            $callabe($this);
        }

        return $this;
    }

    /**
     * clause left join
     *
     * @param string   $table
     * @param callable $callable
     *
     * @throws QueryBuilderException
     * @return QueryBuilder
     */
    public function leftJoin($table, callable $callable = null)
    {
        $table = $this->getPrefix().$table;

        if (is_null($this->join)) {
            $this->join = 'left join `'.$table.'`';

            if (is_callable($callable)) {
                $callable($this);
            }

            return $this;
        }

        if (!preg_match('/^(inner|right)\sjoin\s.*/', $this->join)) {
            $this->join .= ', `'.$table.'`';

            if (is_callable($callable)) {
                $callable($this);
            }

            return $this;
        }

        throw new QueryBuilderException('La clause inner join est dèja initalisé.', E_ERROR);
    }

    /**
     * clause right join
     *
     * @param string   $table
     * @param callable $callable
     *
     * @throws QueryBuilderException
     * @return QueryBuilder
     */
    public function rightJoin($table, callable $callable)
    {
        $table = $this->getPrefix().$table;

        if (is_null($this->join)) {
            $this->join = 'right join `'.$table.'`';

            if (is_callable($callable)) {
                $callable($this);
            }

            return $this;
        }

        if (!preg_match('/^(inner|left)\sjoin\s.*/', $this->join)) {
            $this->join .= ', `'.$table.'`';

            if (is_callable($callable)) {
                $callable($this);
            }

            return $this;
        }

        throw new QueryBuilderException('La clause inner join est dèja initialisé.', E_ERROR);
    }

    /**
     * On, Si chainé avec lui même doit ajouter un <<and>> avant, sinon
     * si chainé avec <<orOn>> orOn ajout un <<or>> dévant
     *
     * @param string $first
     * @param string $comp
     * @param string $second
     *
     * @throws QueryBuilderException
     *
     * @return QueryBuilder
     */
    public function on($first, $comp = '=', $second = null)
    {
        if (is_null($this->join)) {
            throw new QueryBuilderException('La clause inner join est dèja initialisé.', E_ERROR);
        }

        if (!$this->isComporaisonOperator($comp)) {
            $second = $comp;
        }

        if (count(explode('.', $first)) == 2) {
            $first = $this->getPrefix().$first;
        }

        if (count(explode('.', $second)) == 2) {
            $second = $this->getPrefix().$second;
        }

        if (!preg_match('/on/i', $this->join)) {
            $this->join .= ' on `' . $first . '` ' . $comp . ' `' . $second . '`';
        } else {
            $this->join .= ' and `' . $first . '` ' . $comp . ' `' . $second . '`';
        }

        return $this;
    }

    /**
     * clause On, suivie d'une combinaison par un comparateur <<or>>
     * Il faut que l'utilisateur fasse un <<on()>> avant d'utiliser le <<orOn>>
     *
     * @param string $first
     * @param string $comp
     * @param string $second
     *
     * @throws QueryBuilderException
     *
     * @return QueryBuilder
     */
    public function orOn($first, $comp = '=', $second = null)
    {
        if (is_null($this->join)) {
            throw new QueryBuilderException('La clause inner join est dèja initialisé.', E_ERROR);
        }

        if (!$this->isComporaisonOperator($comp)) {
            $second = $comp;
        }

        if (!preg_match('/on/i', $this->join)) {
            throw new QueryBuilderException('La clause <b>on</b> n\'est pas initialisé.', E_ERROR);
        }

        if (count(explode('.', $first)) == 2) {
            $first = $this->getPrefix().$first;
        }

        if (count(explode('.', $second)) == 2) {
            $second = $this->getPrefix().$second;
        }

        $this->join .= ' or `'.$first.'` '.$comp.' '.$second;

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
     * @param null   $value
     * @param string $boolean
     * @return self
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

        return $this;
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
        if (! is_null($this->order)) {
            return $this;
        }

        if (!in_array($type, ['asc', 'desc'])) {
            $type = 'asc';
        }

        $this->order = 'order by `'.$column.'` '.$type;

        return $this;
    }

    /**
     * jump = offset
     *
     * @param  int $offset
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
     * @param string   $column
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

            if (!isNull($this->havin)) {
                $sql .= ' having ' . $this->havin;
            }
        }

        $s = $this->connection->prepare($sql);

        $this->bind($s, $this->whereDataBinding);

        $s->execute();

        if ($s->rowCount() > 1) {
            return Sanitize::make($s->fetchAll());
        }

        return (int) $s->fetchColumn();
    }

    /**
     * Action get, seulement sur la requete de type select
     *  Si le mode de séléction unitaire n'est pas active
     *
     * @param  array $columns
     * @return array|stdClass
     * @throws
     */
    public function get(array $columns = [])
    {
        if (count($columns) > 0) {
            $this->select($columns);
        }

        // Execution de requete.
        $sql = $this->toSql();

        $stmt = $this->connection->prepare($sql);

        $this->bind($stmt, $this->whereDataBinding);

        $this->whereDataBinding = [];
        $stmt->execute();

        $data = Sanitize::make($stmt->fetchAll());

        $stmt->closeCursor();

        if (!$this->first) {
            return $data;
        }
   
        $current = current($data);
        
        $this->first = false;

        if ($current == false) {
            return null;
        }

        return $current;
    }

    /**
     * Alias de getOne
     *
     * @return \stdClass|null
     */
    public function first()
    {
        $this->first = true;

        $this->limit = 1;

        return $this->get();
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

        return $this->jump($c - 1)
            ->take(1)->first();
    }

    /**
     * Demarrer un transaction dans la base de donnée.
     *
     * @param  callable $cb
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
     * @param string $column La colonne sur laquelle sera faite le `count`
     *
     * @return int
     */
    public function count($column = '*')
    {
        if ($column != '*') {
            $column = '`' . $column . '`';
        }

        $sql = 'select count(' . $column . ') from `' . $this->table .'`';

        if ($this->where !== null) {
            $sql .= ' where ' . $this->where;

            $this->where = null;
        }

        $stmt = $this->connection->prepare($sql);

        $this->bind($stmt, $this->whereDataBinding);

        $this->whereDataBinding = [];

        $stmt->execute();

        $r = $stmt->fetchColumn();

        return (int) $r;
    }

    /**
     * Action update
     *
     * @param array $data
     * @return int
     */
    public function update(array $data = [])
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

        $data = Sanitize::make($data, true);

        $this->bind($stmt, $data);

        // execution de la requête
        $stmt->execute();

        $r = $stmt->rowCount();

        return (int) $r;
    }

    /**
     * Action delete
     *
     * @return int
     */
    public function delete()
    {
        $sql = 'delete from `' . $this->table . '`';

        if (!is_null($this->where)) {
            $sql .= ' where ' . $this->where;

            $this->where = null;
        }

        $stmt = $this->connection->prepare($sql);

        $this->bind($stmt, $this->whereDataBinding);

        $this->whereDataBinding = [];

        $stmt->execute();

        $r = $stmt->rowCount();

        return (int) $r;
    }

    /**
     * remove alise simplifié de delete.
     *
     * @param string $column
     * @param string $comp
     * @param string $value
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
     * @param string $column
     * @param int $step
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
     * @param int    $step
     *
     * @return int
     */
    public function decrement($column, $step = 1)
    {
        return $this->incrementAction($column, $step, '-');
    }

    /**
     * Permet de faire une réquete avec la close DISTINCT
     *
     * @param  string $column
     * @return QueryBuilder
     */
    public function distinct($column)
    {
        if (!is_null($this->select)) {
            $this->select .= " distinct `$column`";
        } else {
            $this->select = "distinct `$column`";
        }

        return $this;
    }

    /**
     * method permettant de customiser les methods increment et decrement
     *
     * @param string $column
     * @param int    $step
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
     * Action truncate, vide la Builder
     *
     * @return bool
     */
    public function truncate()
    {
        return (bool) $this->connection
            ->exec('truncate `' . $this->table . '`;');
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

        if (!empty($resets)) {
            $nInserted += $this->insertOne($resets);
        }

        return $nInserted;
    }

    /**
     * insert one
     *
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
     * @return int
     */
    public function insertAndGetLastId(array $values)
    {
        $this->insert($values);

        $n = $this->connection->lastInsertId();

        return $n;
    }

    /**
     * Action drop, supprime la Builder
     *
     * @return mixed
     */
    public function drop()
    {
        return (bool) $this->connection
            ->exec('drop table ' . $this->table);
    }

    /**
     * Utilitaire isComporaisonOperator, permet valider un opérateur
     *
     * @param string $comp
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
     * @param  integer $n
     * @param  integer $current
     * @param  integer $chunk
     *
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

        $data = $this->jump($jump)
            ->take($n)->get();

        // reinitialisation du where
        $this->where = $where;

        $this->whereDataBinding = $dataBind;

        // On compte le nombre de page qui reste
        $restOfPage = ceil($this->count() / $n) - $current;

        // groupé les données
        if (is_int($chunk)) {
            $data = array_chunk($data, $chunk);
        }

        // Active la pagination automatique.
        $data = [
            'next' => $current >= 1 && $restOfPage > 0 ? $current + 1 : false,
            'previous' => ($current - 1) <= 0 ? 1 : ($current - 1),
            'total' => (int) ($restOfPage + $current),
            'current' => $current,
            'data' => $data
        ];

        return new Collection($data);
    }

    /**
     * vérifie si un valeur existe déjà dans la DB
     *
     * @param  string $column Le nom de la colonne a vérifié
     * @param  mixed  $value  Le valeur de la colonne
     * @return bool
     * @throws QueryBuilderException
     */
    public function exists($column = null, $value = null)
    {
        if ($column == null && $value == null) {
            return $this->count() > 0;
        }

        return $this->where($column, $value)->count() > 0;
    }

    /**
     * rétourne l'id de la dernière insertion
     *
     * @param  string $name [optional]
     * @return string
     */
    public function getLastInsertId($name = null)
    {
        return $this->connection->lastInsertId($name);
    }

    /**
     * @return string
     */
    public function jsonSerialize()
    {
        return json_encode($this->get());
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
     * Formate la requete select
     *
     * @return string
     */
    public function toSql()
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
            $sql .= ' ' . $this->join;

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
     * Permet de retourner le nom de la table.
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Permet de retourner le prefixage.
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Permet de modifier le prefix
     *
     * @param string $prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * Permet de modifier le mom de la table
     *
     * @param string $table
     */
    public function setTable($table)
    {
        $this->table = $table;
    }

    /**
     * Permet de définir les données à associer
     *
     * @param array $whereDataBinding
     */
    public function setWhereDataBinding($whereDataBinding)
    {
        $this->whereDataBinding = $whereDataBinding;
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
