<?php

declare(strict_types=1);

namespace Bow\Database;

use PDO;
use stdClass;
use PDOStatement;
use Bow\Support\Str;
use Bow\Support\Util;
use Bow\Security\Sanitize;
use Bow\Database\Exception\QueryBuilderException;

class QueryBuilder implements \JsonSerializable
{
    /**
     * The table name
     *
     * @var string
     */
    protected ?string $table = null;

    /**
     * Select statement collector
     *
     * @var string
     */
    protected ?string $select = null;

    /**
     * Where statement collector
     *
     * @var string
     */
    protected ?string $where = null;

    /**
     * The data binding information
     *
     * @var array
     */
    protected array $where_data_binding = [];

    /**
     * Join statement collector
     *
     * @var string
     */
    protected ?string $join = null;

    /**
     * Limit statement collector
     *
     * @var string
     */
    protected ?string $limit = null;

    /**
     * Group statement collector
     *
     * @var string
     */
    protected ?string $group = null;

    /**
     * Having statement collector
     *
     * @var string
     */
    protected ?string $having = null;

    /**
     * Order By statement collector
     *
     * @var string
     */
    protected ?string $order = null;

    /**
     * Define the table as
     *
     * @var string
     */
    protected ?string $as = null;

    /**
     * The PDO instance
     *
     * @var \PDO
     */
    protected ?\PDO $connection = null;

    /**
     * Define whether to retrieve information from the list
     *
     * @var bool
     */
    protected bool $first = false;

    /**
     * The table prefix
     *
     * @var string
     */
    protected string $prefix = '';

    /**
     * QueryBuilder Constructor
     *
     * @param string $table
     * @param PDO $connection
     */
    public function __construct(string $table, PDO $connection)
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

            return $this;
        }

        if (is_null($this->select)) {
            $this->select = '';
        }

        // Transaction Query builder to SQL for subquery
        foreach ($select as $key => $value) {
            if ($value instanceof QueryBuilder) {
                $select[$key] = '(' . $value->toSql() . ')';
            }
        }

        if (!is_null($this->select)) {
            $this->select .= ", ";
        }

        $this->select .= implode(', ', $select);

        $this->select = trim($this->select, ', ');

        return $this;
    }

    /**
     * Create the table as
     *
     * @param string $as
     * @return QueryBuilder
     */
    public function as(string $as): QueryBuilder
    {
        $this->as = $as;

        return $this;
    }

    /**
     * Add where clause into the request
     *
     * WHERE column1 $comparator $value|column
     *
     * @param string $column
     * @param mixed $comparator
     * @param mixed $value
     * @param string $boolean
     * @throws QueryBuilderException
     * @return QueryBuilder
     */
    public function where(
        string $column,
        mixed $comparator = '=',
        mixed $value = null,
        string $boolean = 'and'
    ): QueryBuilder {

    // We check here the applied comparator
        if (!static::isComparisonOperator($comparator) || is_null($value)) {
            $value = $comparator;

            $comparator = '=';
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
            $indicator = "(" . $value->toSql() . ")";
        } else {
            $indicator = "?";
            $this->where_data_binding[] = $value;
        }

        if ($this->where == null) {
            $this->where = $column . ' ' . $comparator . ' ' . $indicator;
        } else {
            $this->where .= ' ' . $boolean . ' ' . $column . ' ' . $comparator . ' ' . $indicator;
        }

        return $this;
    }

    /**
     * Add where clause into the request
     *
     * WHERE column1 $comparator $value|column
     *
     * @param string $where
     * @return QueryBuilder
     */
    public function whereRaw(string $where): QueryBuilder
    {
        if ($this->where == null) {
            $this->where = $where;
        } else {
            $this->where .= ' and ' . $where;
        }

        return $this;
    }

    /**
     * orWhere, add a condition of type:
     *
     * [where column = value or column = value]
     *
     * @param string $column
     * @param mixed $comparator
     * @param mixed  $value
     * @throws QueryBuilderException
     * @return QueryBuilder
     */
    public function orWhere(string $column, mixed $comparator = '=', mixed $value = null): QueryBuilder
    {
        if (is_null($this->where)) {
            throw new QueryBuilderException(
                'This function can not be used without a where before.',
                E_ERROR
            );
        }

        return $this->where($column, $comparator, $value, 'or');
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
    public function whereNull(string $column, string $boolean = 'and'): QueryBuilder
    {
        if (is_null($this->where)) {
            $this->where = $column . ' is null';
        } else {
            $this->where .= ' ' . $boolean . ' ' . $column . ' is null';
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
    public function whereNotNull($column, $boolean = 'and'): QueryBuilder
    {
        if (is_null($this->where)) {
            $this->where = $column . ' is not null';
        } else {
            $this->where .= ' ' . $boolean . ' ' . $column . ' is not null';
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
     * @param string $boolean
     * @throws QueryBuilderException
     * @return QueryBuilder
     */
    public function whereBetween(string $column, array $range, string $boolean = 'and'): QueryBuilder
    {
        $range = (array) $range;
        $between = implode(' and ', $range);

        if (is_null($this->where)) {
            if ($boolean == 'not') {
                $this->where = $column . ' not between ' . $between;
            } else {
                $this->where = $column . ' between ' . $between;
            }
        } else {
            if ($boolean == 'not') {
                $this->where .= ' and ' . $column . ' not between ' . $between;
            } else {
                $this->where .= ' ' . $boolean . ' ' . $column . ' between ' . $between;
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
    public function whereNotBetween(string $column, array $range): QueryBuilder
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
    public function whereIn(string $column, array $range, string $boolean = 'and'): QueryBuilder
    {
        if ($range instanceof QueryBuilder) {
            $range = "(" . $range->toSql() . ")";
        }

        if (is_array($range)) {
            $range = (array) $range;
            $this->where_data_binding = array_merge($this->where_data_binding, $range);

            $map = array_map(fn() => '?', $range);
            $in = implode(', ', $map);
        } else {
            $in = (string) $range;
        }

        if (is_null($this->where)) {
            if ($boolean == 'not') {
                $this->where = $column . ' not in (' . $in . ')';
            } else {
                $this->where = $column . ' in (' . $in . ')';
            }
        } else {
            if ($boolean == 'not') {
                $this->where .= ' and ' . $column . ' not in (' . $in . ')';
            } else {
                $this->where .= ' and ' . $column . ' in (' . $in . ')';
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
    public function whereNotIn(string $column, array $range)
    {
        $this->whereIn($column, $range, 'not');

        return $this;
    }

    /**
     * Join clause
     *
     * @param string  $table
     * @param string $first
     * @param mixed $comparator
     * @param string $second
     * @return QueryBuilder
     */
    public function join(
        string $table,
        string $first,
        mixed $comparator = '=',
        ?string $second = null
    ): QueryBuilder {
        $table = $this->getPrefix() . $table;

        if (is_null($this->join)) {
            $this->join = '';
        } else {
            $this->join .= ' ';
        }

        // We check here the applied comparator
        if (!$this->isComparisonOperator($comparator)) {
            $second = $comparator;
            $comparator = '=';
        }

        // Building the join query
        $this->join .= 'inner join ' . $table . ' on ' . $first . ' ' . $comparator . ' ' . $second;

        return $this;
    }

    /**
     * Left Join clause
     *
     * @param string $table
     * @param string $first
     * @param mixed $comparator
     * @param string $second
     * @throws QueryBuilderException
     * @return QueryBuilder
     */
    public function leftJoin(
        string $table,
        string $first,
        mixed $comparator = '=',
        ?string $second = null
    ): QueryBuilder {
        $table = $this->getPrefix() . $table;

        if (is_null($this->join)) {
            $this->join = '';
        } else {
            $this->join .= ' ';
        }

        // We check here the applied comparator
        if (!$this->isComparisonOperator($comparator)) {
            $second = $comparator;
            $comparator = '=';
        }

        // Building the join query
        $this->join .= 'left join ' . $table . ' on ' . $first . ' ' . $comparator . ' ' . $second . ' ';

        return $this;
    }

    /**
     * Right Join clause
     *
     * @param string $table
     * @param string $first
     * @param mixed $comparator
     * @param string $second
     * @throws QueryBuilderException
     * @return QueryBuilder
     */
    public function rightJoin(
        string $table,
        string $first,
        mixed $comparator = '=',
        ?string $second = null
    ): QueryBuilder {
        $table = $this->getPrefix() . $table;

        if (is_null($this->join)) {
            $this->join = '';
        } else {
            $this->join .= ' ';
        }

        // We check here the applied comparator
        if (!$this->isComparisonOperator($comparator)) {
            $second = $comparator;
            $comparator = '=';
        }

        $this->join .= 'right join ' . $table . ' on ' . $first . ' ' . $comparator . ' ' . $second;

        return $this;
    }

    /**
     * On, if chained with itself must add an << and >> before, otherwise
     * if chained with "orOn" who add a "before"
     *
     * @param string $first
     * @param mixed $comparator
     * @param string $second
     * @throws QueryBuilderException
     * @return QueryBuilder
     */
    public function andOn(string $first, $comparator = '=', $second = null): QueryBuilder
    {
        if (is_null($this->join)) {
            throw new QueryBuilderException(
                'The inner join clause is already initialized.',
                E_ERROR
            );
        }

        // We check here the applied comparator
        if (!$this->isComparisonOperator($comparator)) {
            $second = $comparator;
            $comparator = '=';
        }

        $this->join .= ' and ' . $first . ' ' . $comparator . ' ' . $second;

        return $this;
    }

    /**
     * Clause On, followed by a combination by a comparator <<or>>
     * The user has to do an "on()" before using the "orOn"
     *
     * @param string $first
     * @param mixed $comparator
     * @param string $second
     * @throws QueryBuilderException
     * @return QueryBuilder
     */
    public function orOn(string $first, $comparator = '=', $second = null): QueryBuilder
    {
        if (is_null($this->join)) {
            throw new QueryBuilderException(
                'The inner join clause is already initialized.',
                E_ERROR
            );
        }

        // We check here the applied comparator
        if (!$this->isComparisonOperator($comparator)) {
            $second = $comparator;
            $comparator = '=';
        }

        $this->join .= ' or ' . $first . ' ' . $comparator . ' ' . $second;

        return $this;
    }

    /**
     * Clause Group By
     *
     * @param string $column
     * @return QueryBuilder
     */
    public function groupBy(string $column): QueryBuilder
    {
        if (is_null($this->group)) {
            $this->group = $column;
        }

        return $this;
    }

    /**
     * Clause Group By
     *
     * @deprecated
     * @param string $column
     * @return QueryBuilder
     */
    public function group($column)
    {
        return $this->groupBy($column);
    }

    /**
     * clause having, is used with a groupBy
     *
     * @param string $column
     * @param mixed $comparator
     * @param mixed  $value
     * @param string $boolean
     * @return QueryBuilder
     */
    public function having(
        string $column,
        mixed $comparator = '=',
        $value = null,
        $boolean = 'and'
    ): QueryBuilder {
        // We check here the applied comparator
        if (!$this->isComparisonOperator($comparator)) {
            $value = $comparator;
            $comparator = '=';
        }

        if (is_null($this->having)) {
            $this->having = $column . ' ' . $comparator . ' ' . $value;
        } else {
            $this->having .= ' ' . $boolean . ' ' . $column . ' ' . $comparator . ' ' . $value;
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
    public function orderBy(string $column, string $type = 'asc'): QueryBuilder
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
    public function jump(int $offset = 0): QueryBuilder
    {
        // Check the limit value definition
        if (is_null($this->limit) || strlen(trim($this->limit)) === 0) {
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
    public function take(int $limit): QueryBuilder
    {
        if (is_null($this->limit)) {
            $this->limit = (string) $limit;

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
     * @return int|float
     */
    public function max(string $column): int|float
    {
        return $this->aggregate('max', $column);
    }

    /**
     * Min
     *
     * @param string $column
     * @return int|float
     */
    public function min($column): int|float
    {
        return $this->aggregate('min', $column);
    }

    /**
     * Avg
     *
     * @param string $column
     * @return int|float
     */
    public function avg($column): int|float
    {
        return $this->aggregate('avg', $column);
    }

    /**
     * Sum
     *
     * @param string $column
     * @return int|float
     */
    public function sum($column): int|float
    {
        return $this->aggregate('sum', $column);
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
     * Internally launches queries that use aggregates.
     *
     * @param $aggregate
     * @param string $column
     * @return int|float
     */
    private function aggregate($aggregate, $column): int|float
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

            if (!is_null($this->having)) {
                $sql .= ' having ' . $this->having;
            }
        }

        $statement = $this->connection->prepare($sql);

        $this->bind($statement, $this->where_data_binding);
        $this->where_data_binding = [];

        $statement->execute();

        if ($statement->rowCount() > 1) {
            return Sanitize::make($statement->fetchAll());
        }

        // Notice: The result of the next action can be float or int type
        return $statement->fetchColumn();
    }

    /**
     * Get make, only on the select request
     * If the first selection mode is not active
     *
     * @param  array $columns
     * @return array|object|null
     * @throws
     */
    public function get(array $columns = []): array|object|null
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
    public function first(): ?object
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
    public function last(): ?object
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
     * Update action
     *
     * @param array $data
     * @return int
     */
    public function update(array $data = []): int
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
    public function delete(): int
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
     * @param mixed $comparator
     * @param string $value
     * @return int
     * @throws QueryBuilderException
     */
    public function remove(string $column, mixed $comparator = '=', $value = null): int
    {
        $this->where = null;

        return $this->where($column, $comparator, $value)->delete();
    }

    /**
     * Action increment, add 1 by default to the specified field
     *
     * @param string $column
     * @param int $step
     *
     * @return int
     */
    public function increment(string $column, int $step = 1): int
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
    public function decrement(string $column, int $step = 1): int
    {
        return $this->incrementAction($column, $step, '-');
    }

    /**
     * Allows a query with the DISTINCT clause
     *
     * @param  string $column
     * @return QueryBuilder
     */
    public function distinct(string $column)
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
     * @param string $direction
     * @return int
     */
    private function incrementAction(string $column, int $step = 1, string $direction = '+')
    {
        $sql = 'update ' . $this->table . ' set ' . $column . ' = ' . $column . ' ' . $direction . ' ' . $step;

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
    public function truncate(): bool
    {
        if ($this->connection->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $query = 'delete from `' . $this->table . '`;';
            if (!$this->connection->inTransaction()) {
                $query .= ' VACUUM;';
            }
        } else {
            $query = 'truncate table `' . $this->table . '`;';
        }

        return (bool) $this->connection->exec($query);
    }

    /**
     * Insert Action
     *
     * The data to be inserted into the database.
     *
     * @param array $values
     * @return int
     */
    public function insert(array $values): int
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
    private function insertOne(array $value): int
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
     * @return string|int|bool
     */
    public function insertAndGetLastId(array $values): string|int|bool
    {
        $this->insert($values);

        $result = $this->connection->lastInsertId();

        return is_numeric($result) ? (int) $result : $result;
    }

    /**
     * Drop Action, remove the table
     *
     * @return mixed
     */
    public function drop(): bool
    {
        return (bool) $this->connection->exec('drop table ' . $this->table);
    }

    /**
     * Paginate, make pagination system
     *
     * @param int $number_of_page
     * @param int $current
     * @param int $chunk
     * @return array
     */
    public function paginate(int $number_of_page, int $current = 0, int $chunk = null): array
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
    public function exists(?string $column = null, mixed $value = null): bool
    {
        if ($column == null && $value == null) {
            return $this->count() > 0;
        }

        return $this->whereIn($column, (array) $value)->count() > 0;
    }

    /**
     * Turn back the id of the last insertion
     *
     * @param  string $name [optional]
     * @return string
     */
    public function getLastInsertId(?string $name = null)
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
    public function toJson(int $option = 0): string
    {
        return json_encode($this->get(), $option);
    }

    /**
     * Formats the select request
     *
     * @return string
     */
    public function toSql(): string
    {
        $sql = 'select ';

        // Adding the select clause
        if (is_null($this->select)) {
            $sql .= '* from ' . $this->getTable();
        } else {
            $sql .= $this->select . ' from ' . $this->getTable();

            $this->select = null;
        }

        if (!is_null($this->as)) {
            $sql .= ' as ' . $this->as;

            $this->as = null;
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
    public function getTable(): string
    {
        return $this->prefix . $this->table;
    }

    /**
     * Returns the prefix.
     *
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * Modify the prefix
     *
     * @param string $prefix
     */
    public function setPrefix(string $prefix): QueryBuilder
    {
        $this->prefix = $prefix;

        $this->table = $this->getPrefix() . $table;

        return $this;
    }

    /**
     * Change the table's name
     *
     * @param string $table
     * @return QueryBuilder
     */
    public function setTable(string $table): QueryBuilder
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Define the data to associate
     *
     * @param array $data_binding
     * @return void
     */
    public function setWhereDataBinding(array $data_binding): void
    {
        $this->where_data_binding = $data_binding;
    }

    /**
     * __toString
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toJson();
    }

    /**
     * Executes PDOStatement::bindValue on an instance of
     *
     * @param PDOStatement $pdo_statement
     * @param array $bindings
     *
     * @return PDOStatement
     */
    private function bind(PDOStatement $pdo_statement, array $bindings = []): PDOStatement
    {
        foreach ($bindings as $key => $value) {
            if (is_null($value) || strtolower((string) $value) === 'null') {
                $pdo_statement->bindValue(
                    ':' . $key,
                    $value,
                    PDO::PARAM_NULL
                );
                unset($bindings[$key]);
            }
        }

        foreach ($bindings as $key => $value) {
            $param = PDO::PARAM_INT;

            /**
             * We force the value in whole or in real.
             *
             * SECURITY OF DATA
             * - Injection SQL
             * - XSS
             */
            if (is_int($value)) {
                $value = (int) $value;
            } elseif (is_float($value)) {
                $value = (float) $value;
            } elseif (is_double($value)) {
                $value = (float) $value;
            } elseif (is_resource($value)) {
                $param = PDO::PARAM_LOB;
            } else {
                $param = PDO::PARAM_STR;
            }

            // Bind by value with native pdo statement object
            $pdo_statement->bindValue(
                is_string($key) ? ":" . $key : $key + 1,
                $value,
                $param
            );
        }

        return $pdo_statement;
    }

    /**
     * Utility, allows to validate an operator
     *
     * comparatoram string $comp
     * @return bool
     */
    private static function isComparisonOperator(mixed $comparator): bool
    {
        if (!is_string($comparator)) {
            return false;
        }

        return in_array(Str::upper($comparator), [
            '=', '>', '<', '>=', '=<', '<>', '!=', 'LIKE', 'NOT', 'IS NOT', "IN", "NOT IN"
        ], true);
    }
}
