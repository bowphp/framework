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
    protected $having;

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
     * @param bool $protected
     * @return QueryBuilder
     */
    public function select(array $select = ['*'])
    {
        if (count($select) == 0) {
            return $this;
        }

        if (count($select) == 1 && $select[0] == '*') {
            $this->select = '*';

            return $this;
        }

        if (is_null($this->select)) {
            $this->select = '';
        }

        // Transaction Query builder to SQL for subquery
        foreach ($select as $key => $value) {
            if ($value instanceof QueryBuilder) {
                $select[$key] = $value->toSql();
            }
        }

        $this->select .= implode(', ', $select);

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
        if (!static::isComparisonOperator($comp) || is_null($value)) {
            $value = $comp;

            $comp = '=';
        }

        if ($value === null) {
            throw new QueryBuilderException('Unresolved comparison value', E_ERROR);
        }

        if (!in_array(Str::lower($boolean), ['and', 'or'])) {
            throw new QueryBuilderException(
                'The bool ' . $boolean . ' not accepted',
                E_ERROR
            );
        }

        if ($value instanceof QueryBuilder) {
            $indicator = "(".$value->toSql().")";
        } else {
            $indicator = "?";
            $this->where_data_binding[] = $value;
        }

        if ($this->where == null) {
            $this->where = '(' . $column . ' ' . $comp . ' '.$indicator.')';
        } else {
            $this->where .= ' ' . $boolean . ' (' . $column . ' ' . $comp . ' '.$indicator.')';
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
            $this->where = '(' . $column . ' is null)';
        } else {
            $this->where .= ' ' . $boolean . ' (' . $column . ' is null)';
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
            $this->where = '(' . $column . ' is not null)';
        } else {
            $this->where .= ' ' . $boolean . ' (' . $column . ' is not null)';
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
    public function whereBetween($column, $range, $boolean = 'and')
    {
        $range = (array) $range;
        $between = implode(' and ', $range);

        if (is_null($this->where)) {
            if ($boolean == 'not') {
                $this->where = '(' . $column . ' not between ' . $between . ')';
            } else {
                $this->where = '(' . $column . ' between ' . $between . ')';
            }
        } else {
            if ($boolean == 'not') {
                $this->where .= ' and (' . $column . ' not between ' . $between . ')';
            } else {
                $this->where .= ' ' . $boolean . ' (' . $column . ' between ' . $between . ')';
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
    public function whereNotBetween($column, $range)
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
    public function whereIn($column, $range, $boolean = 'and')
    {
        if ($range instanceof QueryBuilder) {
            $range = "(".$range->toSql().")";
        }

        if (!is_string($range)) {
            $range = (array) $range;
            $this->where_data_binding = array_merge($range, $this->where_data_binding);

            $map = array_map(function () {
                return '?';
            }, $range);
            $in = implode(', ', $map);
        } else {
            $in = $range;
        }

        if (is_null($this->where)) {
            if ($boolean == 'not') {
                $this->where = '(' . $column . ' not in (' . $in . '))';
            } else {
                $this->where = '(' . $column . ' in (' . $in . '))';
            }
        } else {
            if ($boolean == 'not') {
                $this->where .= ' and (' . $column . ' not in (' . $in . '))';
            } else {
                $this->where .= ' and (' . $column . ' in (' . $in . '))';
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
    public function whereNotIn($column, $range)
    {
        $this->whereIn($column, $range, 'not');

        return $this;
    }

    /**
     * Join clause
     *
     * @param string  $table
     * @param string $first
     * @param string $comp
     * @param string $second
     * @return QueryBuilder
     */
    public function join($table, $first, $comp = '=', $second = null)
    {
        $table = $this->getPrefix() . $table;

        if (is_null($this->join)) {
            $this->join = '';
        } else {
            $this->join .= ' ';
        }

        if (!$this->isComparisonOperator($comp)) {
            $second = $comp;
            $comp = '=';
        }

        $this->join .= 'inner join ' . $table . ' on ' . $first . ' ' . $comp . ' ' . $second;

        return $this;
    }

    /**
     * Left Join clause
     *
     * @param string $table
     * @param string $first
     * @param string $comp
     * @param string $second
     * @throws QueryBuilderException
     * @return QueryBuilder
     */
    public function leftJoin($table, $first, $comp = '=', $second = null)
    {
        $table = $this->getPrefix() . $table;

        if (is_null($this->join)) {
            $this->join = '';
        } else {
            $this->join .= ' ';
        }

        if (!$this->isComparisonOperator($comp)) {
            $second = $comp;
            $comp = '=';
        }

        $this->join .= 'left join ' . $table . ' on ' . $first . ' ' . $comp . ' ' . $second . ' ';

        return $this;
    }

    /**
     * Right Join clause
     *
     * @param string $table
     * @param string $first
     * @param string $comp
     * @param string $second
     * @throws QueryBuilderException
     * @return QueryBuilder
     */
    public function rightJoin($table, $first, $comp = '=', $second = null)
    {
        $table = $this->getPrefix() . $table;

        if (is_null($this->join)) {
            $this->join = '';
        } else {
            $this->join .= ' ';
        }

        if (!$this->isComparisonOperator($comp)) {
            $second = $comp;
            $comp = '=';
        }

        $this->join .= 'right join ' . $table . ' on ' . $first . ' ' . $comp . ' ' . $second;

        return $this;
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
    public function andOn($first, $comp = '=', $second = null)
    {
        if (is_null($this->join)) {
            throw new QueryBuilderException(
                'The inner join clause is already initialized.',
                E_ERROR
            );
        }

        if (!$this->isComparisonOperator($comp)) {
            $second = $comp;
            $comp = '=';
        }

        $this->join .= ' and ' . $first . ' ' . $comp . ' ' . $second;

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

        if (!$this->isComparisonOperator($comp)) {
            $second = $comp;
            $comp = '=';
        }

        $this->join .= ' or ' . $first . ' ' . $comp . ' ' . $second;

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
        if (!$this->isComparisonOperator($comp)) {
            $value = $comp;
            $comp = '=';
        }

        if (is_null($this->having)) {
            $this->having = $column . ' ' . $comp . ' ' . $value;
        } else {
            $this->having .= ' ' . $boolean . ' ' . $column . ' ' . $comp . ' ' . $value;
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
        if (!in_array($type, ['asc', 'desc'])) {
            $type = 'asc';
        }

        if (is_null($this->order)) {
            $this->order = 'order by ' . $column . ' ' . strtolower($type);
        } else {
            $this->order .= ', ' . $column . ' ' . strtolower($type);
        }

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
            $this->limit = $offset . ', ';
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
            $this->limit = end($match) . ', ' . $limit;
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
        return $this->aggregate('max', $column);
    }

    /**
     * Min
     *
     * @param string $column
     * @return QueryBuilder|number|object
     */
    public function min($column)
    {
        return $this->aggregate('min', $column);
    }

    /**
     * Avg
     *
     * @param string $column
     * @return QueryBuilder|number|object
     */
    public function avg($column)
    {
        return $this->aggregate('avg', $column);
    }

    /**
     * Sum
     *
     * @param string $column
     * @return QueryBuilder|number|object
     */
    public function sum($column)
    {
        return $this->aggregate('sum', $column);
    }

    /**
     * Internally launches queries that use aggregates.
     *
     * @param $aggregate
     * @param string $column
     * @return QueryBuilder|number|object
     */
    private function aggregate($aggregate, $column)
    {
        $sql = 'select ' . $aggregate . '(' . $column . ') from ' . $this->table;

        // Adding the join clause
        if (!is_null($this->join)) {
            $sql .= ' ' . $this->join;

            $this->join = null;
        }

        if (!is_null($this->where)) {
            $sql .= ' where ' . $this->where;

            $this->where = null;
        }

        if (!is_null($this->group)) {
            $sql .= ' ' . $this->group;

            $this->group = null;

            if (!isNull($this->having)) {
                $sql .= ' having ' . $this->having;
            }
        }

        $statement = $this->connection->prepare($sql);

        $this->bind($statement, $this->where_data_binding);

        $statement->execute();

        if ($statement->rowCount() > 1) {
            return Sanitize::make($statement->fetchAll());
        }

        return (int) $statement->fetchColumn();
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

        $statement = $this->connection->prepare($sql);

        $this->bind($statement, $this->where_data_binding);

        $this->where_data_binding = [];
        $statement->execute();

        $data = Sanitize::make($statement->fetchAll());

        $statement->closeCursor();

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

        $where_data_binding = $this->where_data_binding;

        // We count all.
        $count = $this->count();

        $this->where = $where;

        $this->where_data_binding = $where_data_binding;

        return $this->jump($count - 1)->take(1)->first();
    }

    /**
     * Count
     *
     * @param string $column
     * @return int
     */
    public function count($column = '*')
    {
        return $this->aggregate('count', $column);
    }

    /**
     * Update action
     *
     * @param array $data
     * @return int
     */
    public function update(array $data = [])
    {
        $sql = 'update ' . $this->table . ' set ';
        $sql .= implode(' = ?, ', array_keys($data)) . ' = ?';

        if (!is_null($this->where)) {
            $sql .= ' where ' . $this->where;

            $this->where = null;

            $data = array_merge(array_values($data), $this->where_data_binding);

            $this->where_data_binding = [];
        }

        $statement = $this->connection->prepare($sql);

        $data = Sanitize::make($data, true);

        $this->bind($statement, $data);

        // Execution of the request
        $statement->execute();

        $result = $statement->rowCount();

        return (int) $result;
    }

    /**
     * Delete Action
     *
     * @return int
     */
    public function delete()
    {
        $sql = 'delete from ' . $this->table;

        if (!is_null($this->where)) {
            $sql .= ' where ' . $this->where;

            $this->where = null;
        }

        $statement = $this->connection->prepare($sql);

        $this->bind($statement, $this->where_data_binding);

        $this->where_data_binding = [];

        $statement->execute();

        $result = $statement->rowCount();

        return (int) $result;
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
            $this->select .= ", distinct $column";
        } else {
            $this->select = "distinct $column";
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
        $sql = 'update ' . $this->table . ' set ' . $column . ' = ' . $column . ' ' . $sign . ' ' . $step;

        if (!is_null($this->where)) {
            $sql .= ' where ' . $this->where;

            $this->where = null;
        }

        $statement = $this->connection->prepare($sql);

        $this->bind($statement, $this->where_data_binding);

        $statement->execute();

        return (int) $statement->rowCount();
    }

    /**
     * Truncate Action, empty the table
     *
     * @return bool
     */
    public function truncate()
    {
        return (bool) $this->connection->exec('truncate ' . $this->table . ';');
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
        $row_affected = 0;

        $resets = [];

        foreach ($values as $key => $value) {
            if (is_array($value)) {
                $row_affected += $this->insertOne($value);
            } else {
                $resets[$key] = $value;
            }

            unset($values[$key]);
        }

        if (!empty($resets)) {
            $row_affected += $this->insertOne($resets);
        }

        return $row_affected;
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

        $sql = 'insert into ' . $this->table . '(' . $column . ') values';

        $sql .= '(' . implode(', ', Util::add2points($fields, true)) . ');';

        $statement = $this->connection->prepare($sql);

        $this->bind($statement, $value);

        $statement->execute();

        return (int) $statement->rowCount();
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

        $result = $this->connection->lastInsertId();

        return $result;
    }

    /**
     * Drop Action, remove the table
     *
     * @return mixed
     */
    public function drop()
    {
        return (bool) $this->connection->exec('drop table ' . $this->table);
    }

    /**
     * Utility, allows to validate an operator
     *
     * @param string $comp
     * @return bool
     */
    private static function isComparisonOperator($comp)
    {
        return in_array(Str::upper($comp), ['=', '>', '<', '>=', '=<', '<>', '!=', 'LIKE', 'NOT', 'IS NOT'], true);
    }

    /**
     * Paginate, make pagination system
     *
     * @param int $number_of_page
     * @param int $current
     * @param int $chunk
     * @return Collection
     */
    public function paginate($number_of_page, $current = 0, $chunk = null)
    {
        // We go to back page
        --$current;

        // Variable containing the number of jump. $jump;
        if ($current <= 0) {
            $jump = 0;
            $current = 1;
        } else {
            $jump = $number_of_page * $current;
            $current++;
        }

        // Saving information about current query
        $where = $this->where;
        $join = $this->join;
        $data_bind = $this->where_data_binding;

        $data = $this->jump($jump)->take($number_of_page)->get();

        // Reinitialisation of current query
        $this->where = $where;
        $this->join = $join;
        $this->where_data_binding = $data_bind;

        // We count the number of pages that remain
        $rest_of_page = ceil($this->count() / $number_of_page) - $current;

        // Grouped data
        if (is_int($chunk)) {
            $data = array_chunk($data, $chunk);
        }

        // Enables automatic paging.
        return [
            'next' => $current >= 1 && $rest_of_page > 0 ? $current + 1 : false,
            'previous' => ($current - 1) <= 0 ? 1 : ($current - 1),
            'total' => (int) ($rest_of_page + $current),
            'per_page' => $number_of_page,
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
     * Transformation automatically the result to JSON
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
            $sql .= '* from ' . $this->table;
        } else {
            $sql .= $this->select . ' from ' . $this->table;

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

            if (!is_null($this->having)) {
                $sql .= ' having ' . $this->having;
            }
        }

        return $sql;
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
        $this->table = $this->getPrefix() . $table;

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
