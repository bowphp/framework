<?php

namespace Bow\Database;

use Bow\Database\Exception\QueryBuilderException;
use Bow\Security\Sanitize;
use Bow\Support\Str;
use Bow\Support\Util;
use PDO;
use stdClass;

class QueryBuilder extends Tool implements \JsonSerializable
{
    /**
     * The table name
     *
     * @var string
     */
    protected $table;

    /**
     * Select statement collector
     *
     * @var string
     */
    protected $select;

    /**
     * Where statement collector
     *
     * @var string
     */
    protected $where;

    /**
     * The data binding information
     *
     * @var array
     */
    protected $where_data_binding = [];

    /**
     * Join statement collector
     *
     * @var string
     */
    protected $join;

    /**
     * Limit statement collector
     *
     * @var string
     */
    protected $limit;

    /**
     * Group statement collector
     *
     * @var string
     */
    protected $group;

    /**
     * Having statement collector
     *
     * @var string
     */
    protected $havin;

    /**
     * Order By statement collector
     *
     * @var string
     */
    protected $order;

    /**
     * The PDO instance
     *
     * @var \PDO
     */
    protected $connection;

    /**
     * Define whether to retrieve information from the list
     *
     * @var bool
     */
    protected $first = false;

    /**
     * The table prefix
     *
     * @var string
     */
    protected $prefix = '';

    /**
     * QueryBuilder Constructor
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
     * Add select column.
     *
     * SELECT $column | SELECT column1, column2, ...
     *
     * @param array $select
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
     * Add where clause into the request
     *
     * WHERE column1 $comp $value|column
     *
     * @param string $column
     * @param string $comp
     * @param mixed $value
     * @param string $boolean
     * @throws QueryBuilderException
     * @return QueryBuilder
     */
    public function where($column, $comp = '=', $value = null, $boolean = 'and')
    {
        if (!static::isComporaisonOperator($comp) || is_null($value)) {
            $value = $comp;

            $comp = '=';
        }

        if ($value === null) {
            throw new QueryBuilderException('Unresolved comparison value', E_ERROR);
        }

        if (!in_array(Str::lower($boolean), ['and', 'or'])) {
            throw new QueryBuilderException(
                'The bool '. $boolean . ' not accepted',
                E_ERROR
            );
        }

        $this->where_data_binding[$column] = $value;

        if ($this->where == null) {
            $this->where = '('. $column . ' ' . $comp . ' :' . $column . ')';
        } else {
            $this->where .= ' ' . $boolean . ' ('. $column . ' '. $comp .' :'. $column. ')';
        }

        return $this;
    }

    /**
     * orWhere, add a condition of type:
     *
     * [where column = value or column = value]
     *
     * @param string $column
     * @param string $comp
     * @param mixed   $value
     * @throws QueryBuilderException
     * @return QueryBuilder
     */
    public function orWhere($column, $comp = '=', $value = null)
    {
        if (is_null($this->where)) {
            throw new QueryBuilderException(
                'This function can not be used without a where before.',
                E_ERROR
            );
        }

        return $this->where($column, $comp, $value, 'or');
    }

    /**
     * Where clause with comparison in << is null >>
     *
     * WHERE column IS NULL
     *
     * @param string $column
     * @param string $boolean
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
     * Where clause with comparison in <<not null>>
     *
     * WHERE column NOT NULL
     *
     * @param $column
     * @param string $boolean
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
     * Where clause with comparison <<between>>
     *
     * WHERE column BETWEEN '' AND ''
     *
     * @param string $column
     * @param array $range
     * @param string boolean
     * @throws QueryBuilderException
     * @return QueryBuilder
     */
    public function whereBetween($column, array $range, $boolean = 'and')
    {
        if (count($range) !== 2) {
            throw new QueryBuilderException(
                'Parameter 2 must be contains two values.',
                E_ERROR
            );
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
     * @param string $column
     * @param array $range
     * @return QueryBuilder
     */
    public function whereNotBetween($column, array $range)
    {
        $this->whereBetween($column, $range, 'not');

        return $this;
    }

    /**
     * Where clause with <<in>> comparison
     *
     * @param string $column
     * @param array  $range
     * @param string $boolean
     * @throws QueryBuilderException
     * @return QueryBuilder
     */
    public function whereIn($column, array $range, $boolean = 'and')
    {
        if (count($range) == 0) {
            throw new QueryBuilderException(
                'Parameter 2 must be contains two values',
                E_ERROR
            );
        }

        $map = array_map(function () {
            return '?';
        }, $range);

        $this->where_data_binding = array_merge($range, $this->where_data_binding);

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
     * Where clause with <<not in>> comparison
     *
     * @param string $column
     * @param array  $range
     * @throws QueryBuilderException
     * @return QueryBuilder
     */
    public function whereNotIn($column, array $range)
    {
        $this->whereIn($column, $range, 'not');

        return $this;
    }

    /**
     * Join clause
     *
     * @param string   $table
     * @param callable $callabe
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
     * Left Join clause
     *
     * @param string $table
     * @param callable $callable
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

        throw new QueryBuilderException(
            'The inner join clause is already in effect.',
            E_ERROR
        );
    }

    /**
     * Right Join clause
     *
     * @param string $table
     * @param callable $callable
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

        throw new QueryBuilderException(
            'The inner join clause is already initialized.',
            E_ERROR
        );
    }

    /**
     * On, if chained with itself must add an << and >> before, otherwise
     * if chained with "orOn" who add a "before"
     *
     * @param string $first
     * @param string $comp
     * @param string $second
     * @throws QueryBuilderException
     * @return QueryBuilder
     */
    public function on($first, $comp = '=', $second = null)
    {
        if (is_null($this->join)) {
            throw new QueryBuilderException(
                'The inner join clause is already initialized.',
                E_ERROR
            );
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
     * Clause On, followed by a combination by a comparator <<or>>
     * The user has to do an "on()" before using the "orOn"
     *
     * @param string $first
     * @param string $comp
     * @param string $second
     * @throws QueryBuilderException
     * @return QueryBuilder
     */
    public function orOn($first, $comp = '=', $second = null)
    {
        if (is_null($this->join)) {
            throw new QueryBuilderException(
                'The inner join clause is already initialized.',
                E_ERROR
            );
        }

        if (!$this->isComporaisonOperator($comp)) {
            $second = $comp;
        }

        if (!preg_match('/on/i', $this->join)) {
            throw new QueryBuilderException(
                'The <b> on </ b> clause is not initialized',
                E_ERROR
            );
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
     * Clause Group By
     *
     * @param string $column
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
     * clause having, is used with a groupBy
     *
     * @param string $column
     * @param string $comp
     * @param mixed  $value
     * @param string $boolean
     * @return QueryBuilder
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
     * Clause Order By
     *
     * @param string $column
     * @param string $type
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
     * Jump = Offset
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
     * Take = Limit
     *
     * @param int $limit
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
     * @return QueryBuilder|number|array|object
     */
    public function max($column)
    {
        return $this->executeAgregat('max', $column);
    }

    /**
     * Min
     *
     * @param string $column
     * @return QueryBuilder|number|object
     */
    public function min($column)
    {
        return $this->executeAgregat('min', $column);
    }

    /**
     * Avg
     *
     * @param string $column
     * @return QueryBuilder|number|object
     */
    public function avg($column)
    {
        return $this->executeAgregat('avg', $column);
    }

    /**
     * Sum
     *
     * @param string $column
     * @return QueryBuilder|number|object
     */
    public function sum($column)
    {
        return $this->executeAgregat('sum', $column);
    }

    /**
     * Internally launches queries that use aggregates.
     *
     * @param $aggregat
     * @param string $column
     * @return QueryBuilder|number|object
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

        $this->bind($s, $this->where_data_binding);

        $s->execute();

        if ($s->rowCount() > 1) {
            return Sanitize::make($s->fetchAll());
        }

        return (int) $s->fetchColumn();
    }

    /**
     * Get make, only on the select request
     * If the first selection mode is not active
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

        // Execution of request.
        $sql = $this->toSql();

        $stmt = $this->connection->prepare($sql);

        $this->bind($stmt, $this->where_data_binding);

        $this->where_data_binding = [];
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
     * Get the first record
     *
     * @return object|null
     */
    public function first()
    {
        $this->first = true;

        $this->take(1);

        return $this->get();
    }

    /**
     * Get the last record
     *
     * @return mixed
     */
    public function last()
    {
        $where = $this->where;

        $whereData = $this->where_data_binding;

        // We count all.
        $c = $this->count();

        $this->where = $where;

        $this->where_data_binding = $whereData;

        return $this->jump($c - 1)
            ->take(1)->first();
    }

    /**
     * Start a transaction in the database.
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
     * Count
     *
     * @param string $column
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

        $this->bind($stmt, $this->where_data_binding);

        $this->where_data_binding = [];

        $stmt->execute();

        $r = $stmt->fetchColumn();

        return (int) $r;
    }

    /**
     * Update action
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

            $data = array_merge($data, $this->where_data_binding);

            $this->where_data_binding = [];
        }

        $stmt = $this->connection->prepare($sql);

        $data = Sanitize::make($data, true);

        $this->bind($stmt, $data);

        // Execution of the request
        $stmt->execute();

        $r = $stmt->rowCount();

        return (int) $r;
    }

    /**
     * Delete Action
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

        $this->bind($stmt, $this->where_data_binding);

        $this->where_data_binding = [];

        $stmt->execute();

        $r = $stmt->rowCount();

        return (int) $r;
    }

    /**
     * Remove simplified stream from delete.
     *
     * @param string $column
     * @param string $comp
     * @param string $value
     * @return int
     * @throws QueryBuilderException
     */
    public function remove($column, $comp = '=', $value = null)
    {
        $this->where = null;

        return $this->where($column, $comp, $value)->delete();
    }

    /**
     * Action increment, add 1 by default to the specified field
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
     * Decrement action, subtracts 1 by default from the specified field
     *
     * @param string $column
     * @param int    $step
     * @return int
     */
    public function decrement($column, $step = 1)
    {
        return $this->incrementAction($column, $step, '-');
    }

    /**
     * Allows a query with the DISTINCT clause
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
     * Method to customize the increment and decrement methods
     *
     * @param string $column
     * @param int    $step
     * @param string $sign
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

        $this->bind($stmt, $this->where_data_binding);

        $stmt->execute();

        return (int) $stmt->rowCount();
    }

    /**
     * Truncate Action, empty the table
     *
     * @return bool
     */
    public function truncate()
    {
        return (bool) $this->connection
            ->exec('truncate `' . $this->table . '`;');
    }

    /**
     * Insert Action
     *
     * The data to be inserted into the database.
     *
     * @param array $values
     * @return int
     */
    public function insert(array $values)
    {
        $n_inserted = 0;

        $resets = [];

        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $n_inserted += $this->insertOne($value);
            } else {
                $resets[$key] = $value;
            }

            unset($values[$key]);
        }

        if (!empty($resets)) {
            $n_inserted += $this->insertOne($resets);
        }

        return $n_inserted;
    }

    /**
     * Insert On, insert one row in the table
     *
     * @see insert
     * @param array $value
     * @return int
     */
    private function insertOne(array $value)
    {
        $fields = array_keys($value);
        $column = implode(', ', $fields);

        $sql = 'insert into `' . $this->table . '`('.$column.') values';

        $sql .= '('.implode(', ', Util::add2points($fields, true)).');';

        $stmt = $this->connection->prepare($sql);

        $this->bind($stmt, $value);

        $stmt->execute();

        return (int) $stmt->rowCount();
    }

    /**
     * InsertAndGetLastId action launches the insert and lastInsertId actions
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
     * Drop Action, remove the table
     *
     * @return mixed
     */
    public function drop()
    {
        return (bool) $this->connection
            ->exec('drop table ' . $this->table);
    }

    /**
     * IsComporaisonOperator utility, allows to validate an operator
     *
     * @param string $comp
     * @return bool
     */
    private static function isComporaisonOperator($comp)
    {
        return in_array($comp, ['=', '>', '<', '>=', '=<', '<>', '!=', 'LIKE', 'like'], true);
    }

    /**
     * Paginate, make pagination system
     *
     * @param int $n
     * @param int $current
     * @param int $chunk
     * @return Collection
     */
    public function paginate($n, $current = 0, $chunk = null)
    {
        // We go a page back
        --$current;

        // Variable containing the number of jump. $jump;

        if ($current <= 0) {
            $jump = 0;

            $current = 1;
        } else {
            $jump = $n * $current;
            
            $current++;
        }

        // Saving information about where
        $where = $this->where;

        $dataBind = $this->where_data_binding;

        $data = $this->jump($jump)
            ->take($n)->get();

        // Reinitialization of where
        $this->where = $where;

        $this->where_data_binding = $dataBind;

        // We count the number of pages that remain
        $restOfPage = ceil($this->count() / $n) - $current;

        // Grouped data
        if (is_int($chunk)) {
            $data = array_chunk($data, $chunk);
        }

        // Enables automatic paging.
        return [
            'next' => $current >= 1 && $restOfPage > 0 ? $current + 1 : false,
            'previous' => ($current - 1) <= 0 ? 1 : ($current - 1),
            'total' => (int) ($restOfPage + $current),
            'per_page' => $n,
            'current' => $current,
            'data' => $data
        ];
    }

    /**
     * Check if a value already exists in the DB
     *
     * @param  string $column
     * @param  mixed  $value
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
     * Turn back the id of the last insertion
     *
     * @param  string $name [optional]
     * @return string
     */
    public function getLastInsertId($name = null)
    {
        return $this->connection->lastInsertId($name);
    }

    /**
     * JsonSerialize implementation
     *
     * @see httsp://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return string
     */
    public function jsonSerialize()
    {
        return json_encode($this->get());
    }

    /**
     * Transformation automaticly the result to JSON
     *
     * @param int $option
     * @return string
     */
    public function toJson($option = 0)
    {
        return json_encode($this->get(), $option);
    }

    /**
     * Formats the select request
     *
     * @return string
     */
    public function toSql()
    {
        $sql = 'select ';

        // Adding the select clause
        if (is_null($this->select)) {
            $sql .= '* from `' . $this->table .'`';
        } else {
            $sql .= $this->select . ' from `' . $this->table . '`';

            $this->select = null;
        }

        // Adding the join clause
        if (!is_null($this->join)) {
            $sql .= ' ' . $this->join;

            $this->join = null;
        }

        // Adding the where clause
        if (!is_null($this->where)) {
            $sql .= ' where ' . $this->where;

            $this->where = null;
        }

        // Addition of the order clause
        if (!is_null($this->order)) {
            $sql .= ' ' . $this->order;

            $this->order = null;
        }

        // Adding the limit clause
        if (!is_null($this->limit)) {
            $sql .= ' limit ' . $this->limit;

            $this->limit = null;
        }

        // Adding the group clause
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
     * Returns the name of the table.
     *
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }

    /**
     * Returns the prefix.
     *
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Modify the prefix
     *
     * @param string $prefix
     */
    public function setPrefix($prefix)
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * Change the table's name
     *
     * @param string $table
     * @return QueryBuilder
     */
    public function setTable($table)
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Define the data to associate
     *
     * @param array $where_data_binding
     */
    public function setWhereDataBinding($where_data_binding)
    {
        $this->where_data_binding = $where_data_binding;
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
