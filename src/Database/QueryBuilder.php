<?php

declare(strict_types=1);

namespace Bow\Database;

use Bow\Database\Connection\AbstractConnection;
use Bow\Database\Exception\QueryBuilderException;
use Bow\Security\Sanitize;
use Bow\Support\Str;
use JsonSerializable;
use PDO;
use PDOStatement;

class QueryBuilder implements JsonSerializable
{
    /**
     * The table name
     *
     * @var ?string
     */
    protected ?string $table = null;

    /**
     * Select statement collector
     *
     * @var ?string
     */
    protected ?string $select = null;

    /**
     * Where statement collector
     *
     * @var ?string
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
     * @var ?string
     */
    protected ?string $join = null;

    /**
     * Limit statement collector
     *
     * @var ?string
     */
    protected ?string $limit = null;

    /**
     * Group statement collector
     *
     * @var ?string
     */
    protected ?string $group = null;

    /**
     * Having statement collector
     *
     * @var ?string
     */
    protected ?string $having = null;

    /**
     * Order By statement collector
     *
     * @var ?string
     */
    protected ?string $order = null;

    /**
     * Define the table as
     *
     * @var ?string
     */
    protected ?string $as = null;

    /**
     * The PDO instance.
     *
     * Only set when the builder is constructed from a raw PDO (no read/write
     * splitting). When built from an adapter, the connection is resolved
     * lazily through {@see QueryBuilder::$connection_adapter}.
     *
     * @var ?PDO
     */
    protected ?PDO $connection = null;

    /**
     * The connection adapter, when read/write splitting is available.
     *
     * @var ?AbstractConnection
     */
    protected ?AbstractConnection $connection_adapter = null;

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
     * The adapter name
     *
     * @var string
     */
    protected string $adapter = '';

    /**
     * Determine the last sql query
     *
     * @var string|null
     */
    protected ?string $last_query = null;

    /**
     * Lock rows for update
     *
     * @var bool
     */
    protected bool $lock_for_update = false;

    /**
     * Lock rows in share mode
     *
     * @var bool
     */
    protected bool $shared_lock = false;

    /**
     * QueryBuilder Constructor
     *
     * @param string                 $table
     * @param AbstractConnection|PDO $connection
     */
    public function __construct(string $table, AbstractConnection|PDO $connection)
    {
        if ($connection instanceof AbstractConnection) {
            $this->connection_adapter = $connection;
            $this->adapter = $connection->getName();
        } else {
            $this->connection = $connection;
            $this->adapter = $connection->getAttribute(PDO::ATTR_DRIVER_NAME);
        }

        $this->table = $table;
    }

    /**
     * Resolve the write (primary) connection.
     *
     * @return PDO
     */
    private function writeConnection(): PDO
    {
        if ($this->connection_adapter !== null) {
            return $this->connection_adapter->getWriteConnection();
        }

        return $this->connection;
    }

    /**
     * Resolve the read (replica) connection.
     *
     * While a transaction is open on the primary, reads are routed to the
     * primary so they observe their own uncommitted changes. Falls back to
     * the single connection when read/write splitting is unavailable.
     *
     * @return PDO
     */
    private function readConnection(): PDO
    {
        if ($this->connection_adapter === null) {
            return $this->connection;
        }

        if (
            $this->connection_adapter->hasWriteConnection()
            && $this->connection_adapter->getWriteConnection()->inTransaction()
        ) {
            return $this->connection_adapter->getWriteConnection();
        }

        return $this->connection_adapter->getReadConnection();
    }

    /**
     * Get the connection adapter name
     *
     * @return string
     */
    public function getAdapterName(): string
    {
        return $this->adapter;
    }

    /**
     * Get the connection
     *
     * @return PDO
     */
    public function getPdo(): PDO
    {
        return $this->writeConnection();
    }

    /**
     * Create the table as
     *
     * @param  string $as
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
     * @param  string $where
     * @param  array  $data
     * @return QueryBuilder
     */
    public function whereRaw(string $where, array $data = []): QueryBuilder
    {
        if ($this->where == null) {
            $this->where = $where;
        } else {
            $this->where .= ' and ' . $where;
        }

        if (!empty($data)) {
            $this->where_data_binding = array_merge($this->where_data_binding, array_values($data));
        }

        return $this;
    }

    /**
     * Add orWhere clause into the request
     *
     * WHERE column1 $comparator $value|column
     *
     * @param  string $where
     * @param  array  $data
     * @return QueryBuilder
     */
    public function orWhereRaw(string $where, array $data = []): QueryBuilder
    {
        if ($this->where == null) {
            $this->where = $where;
        } else {
            $this->where .= ' or ' . $where;
        }

        if (!empty($data)) {
            $this->where_data_binding = array_merge($this->where_data_binding, array_values($data));
        }

        return $this;
    }

    /**
     * orWhere, add a condition of type:
     *
     * [where column = value or column = value]
     *
     * @param  string $column
     * @param  mixed  $comparator
     * @param  mixed  $value
     * @return QueryBuilder
     * @throws QueryBuilderException
     */
    public function orWhere(string $column, mixed $comparator = '=', mixed $value = null): QueryBuilder
    {
        if (is_null($this->where)) {
            throw new QueryBuilderException(
                'This function can not be used without a where before.'
            );
        }

        return $this->where($column, $comparator, $value, 'or');
    }

    /**
     * Add where clause into the request
     *
     * WHERE column1 $comparator $value|column
     *
     * @param  string $column
     * @param  mixed  $comparator
     * @param  mixed  $value
     * @param  string $boolean
     * @return QueryBuilder
     * @throws QueryBuilderException
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
            throw new QueryBuilderException('Unresolved comparison value');
        }

        if (!in_array(Str::lower($boolean), ['and', 'or'])) {
            throw new QueryBuilderException(
                'The bool ' . $boolean . ' not accepted'
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
     * Utility, allows to validate an operator
     *
     * @param  mixed $comparator
     * @return bool
     */
    private static function isComparisonOperator(mixed $comparator): bool
    {
        if (!is_string($comparator)) {
            return false;
        }

        return in_array(Str::upper($comparator), [
            '=',
            '>',
            '<',
            '>=',
            '=<',
            '<>',
            '!=',
            'LIKE',
            'NOT',
            'IS NOT',
            "IN",
            "NOT IN",
            'ILIKE',
            '&',
            '|',
            '<<',
            '>>',
            'NOT LIKE',
            '&&',
            '@>',
            '<@',
            '?',
            '?|',
            '?&',
            '||',
            '-',
            '@?',
            '@@',
            '#-',
            'IS DISTINCT FROM',
            'IS NOT DISTINCT FROM',
        ], true);
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
            $sql .= ' ' . $this->limit;

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

        // Adding the lock for update clause
        if ($this->lock_for_update) {
            $sql .= ' for update';

            $this->lock_for_update = false;
        }

        // Adding the shared lock clause
        if ($this->shared_lock) {
            $sql .= $this->adapter === 'pgsql' ? ' for share' : ' lock in share mode';

            $this->shared_lock = false;
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
     * Change the table's name
     *
     * @param  string $table
     * @return QueryBuilder
     */
    public function setTable(string $table): QueryBuilder
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Where clause with comparison in << is null >>
     *
     * WHERE column IS NULL
     *
     * @param  string $column
     * @return QueryBuilder
     */
    public function whereNull(string $column): QueryBuilder
    {
        if (is_null($this->where)) {
            $this->where = $column . ' is null';
        } else {
            $this->where .= ' and ' . $column . ' is null';
        }

        return $this;
    }

    /**
     * Where clause with comparison in <<not null>>
     *
     * WHERE column NOT NULL
     *
     * @param  string $column
     * @return QueryBuilder
     */
    public function whereNotNull(string $column): QueryBuilder
    {
        if (is_null($this->where)) {
            $this->where = $column . ' is not null';
        } else {
            $this->where .= ' and ' . $column . ' is not null';
        }

        return $this;
    }

    /**
     * WHERE column NOT BETWEEN '' AND ''
     *
     * @param  string $column
     * @param  array  $range
     * @return QueryBuilder
     */
    public function whereNotBetween(string $column, array $range): QueryBuilder
    {
        $range = (array) $range;
        $between = implode(' and ', $range);

        if (is_null($this->where)) {
            $this->where = $column . ' not between ' . $between;
        } else {
            $this->where .= ' and ' . $column . ' not between ' . $between;
        }

        return $this;
    }

    /**
     * Where clause with comparison <<between>>
     *
     * WHERE column BETWEEN '' AND ''
     *
     * @param  string $column
     * @param  array  $range
     * @return QueryBuilder
     */
    public function whereBetween(string $column, array $range): QueryBuilder
    {
        $range = (array) $range;
        $between = implode(' and ', $range);

        if (is_null($this->where)) {
            $this->where = $column . ' between ' . $between;
        } else {
            $this->where .= ' and ' . $column . ' between ' . $between;
        }

        return $this;
    }

    /**
     * WHERE column NOT BETWEEN '' AND ''
     *
     * @param  string $column
     * @param  mixed  $value
     * @return QueryBuilder
     */
    public function whereDifferent(string $column, mixed $value): QueryBuilder
    {
        $this->where($column, '<>', $value);

        return $this;
    }

    /**
     * Where clause with <<not in>> comparison
     *
     * @param  string $column
     * @param  array  $range
     * @return QueryBuilder
     * @throws QueryBuilderException
     */
    public function whereNotIn(string $column, array $range)
    {
        if ($range instanceof QueryBuilder) {
            $range = "(" . $range->toSql() . ")";
        }

        if (is_array($range)) {
            $range = (array)$range;
            $this->where_data_binding = array_merge($this->where_data_binding, $range);

            $map = array_map(fn() => '?', $range);
            $in = implode(', ', $map);
        } else {
            $in = (string) $range;
        }

        if (is_null($this->where)) {
            $this->where = $column . ' not in (' . $in . ')';
        } else {
            $this->where .= ' and ' . $column . ' not in (' . $in . ')';
        }

        return $this;
    }

    /**
     * Where clause with <<in>> comparison
     *
     * @param  string $column
     * @param  array  $range
     * @return QueryBuilder
     * @throws QueryBuilderException
     */
    public function whereIn(string $column, array $range): QueryBuilder
    {
        if ($range instanceof QueryBuilder) {
            $range = "(" . $range->toSql() . ")";
        }

        if (is_array($range)) {
            $range = (array)$range;
            $this->where_data_binding = array_merge($this->where_data_binding, $range);

            $map = array_map(fn() => '?', $range);
            $in = implode(', ', $map);
        } else {
            $in = (string) $range;
        }

        if (is_null($this->where)) {
            $this->where = $column . ' in (' . $in . ')';
        } else {
            $this->where .= ' and ' . $column . ' in (' . $in . ')';
        }

        return $this;
    }

    /**
     * Join clause
     *
     * @param  string $table
     * @param  string $first
     * @param  mixed  $comparator
     * @param  string $second
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
     * @param  string $prefix
     * @return QueryBuilder
     */
    public function setPrefix(string $prefix): QueryBuilder
    {
        $this->prefix = $prefix;

        return $this;
    }

    /**
     * Left Join clause
     *
     * @param  string $table
     * @param  string $first
     * @param  mixed  $comparator
     * @param  string $second
     * @return QueryBuilder
     * @throws QueryBuilderException
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
     * @param  string $table
     * @param  string $first
     * @param  mixed  $comparator
     * @param  string $second
     * @return QueryBuilder
     * @throws QueryBuilderException
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
     * @param  string $first
     * @param  mixed  $comparator
     * @param  string $second
     * @return QueryBuilder
     * @throws QueryBuilderException
     */
    public function andOn(string $first, $comparator = '=', $second = null): QueryBuilder
    {
        if (is_null($this->join)) {
            throw new QueryBuilderException(
                'The inner join clause is already initialized.'
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
     * @param  string $first
     * @param  mixed  $comparator
     * @param  string $second
     * @return QueryBuilder
     * @throws QueryBuilderException
     */
    public function orOn(string $first, $comparator = '=', $second = null): QueryBuilder
    {
        if (is_null($this->join)) {
            throw new QueryBuilderException(
                'The inner join clause is already initialized.',
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
     * @param   string $column
     * @return  QueryBuilder
     * @deprecated
     */
    public function group(string $column)
    {
        return $this->groupBy($column);
    }

    /**
     * Clause Group By
     *
     * @param  string $column
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
     * clause having, is used with a groupBy
     *
     * @param  string $column
     * @param  mixed  $comparator
     * @param  mixed  $value
     * @param  string $boolean
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
     * @param  string $column
     * @param  string $type
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
     * Max
     *
     * @param  string $column
     * @return int|float
     */
    public function max(string $column): int|float
    {
        return $this->aggregate('max', $column);
    }

    /**
     * Internally launches queries that use aggregates.
     *
     * @param  $aggregate
     * @param  string $column
     * @return mixed
     */
    private function aggregate($aggregate, $column): mixed
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

        $statement = $this->execute($sql, $this->where_data_binding, false);

        $this->where_data_binding = [];

        if ($statement->rowCount() > 1) {
            return Sanitize::make($statement->fetchAll());
        }

        // Notice: The result of the next action can be float or int type
        return $statement->fetchColumn() ?? 0;
    }

    /**
     * Executes PDOStatement::bindValue on an instance of
     * Binds parameter values to a PDO statement with proper type detection.
     *
     * Handles type-safe parameter binding for SQL injection prevention.
     *
     * @param PDOStatement $pdo_statement
     * @param array        $bindings
     * @return void
     */
    private function bind(PDOStatement $pdo_statement, array $bindings = []): void
    {
        // Detect if the SQL uses positional or named placeholders
        $sql = $pdo_statement->queryString;
        $uses_named = strpos($sql, ':') !== false;

        if ($uses_named) {
            // Named placeholders
            foreach ($bindings as $key => $value) {
                $param = PDO::PARAM_STR;
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value);
                } elseif (is_null($value) || strtolower((string) $value) === 'null') {
                    $param = PDO::PARAM_NULL;
                } elseif (is_int($value)) {
                    $param = PDO::PARAM_INT;
                } elseif (is_resource($value)) {
                    $param = PDO::PARAM_LOB;
                } elseif (is_bool($value)) {
                    $param = PDO::PARAM_BOOL;
                } elseif (is_string($value)) {
                    $param = PDO::PARAM_STR;
                }
                $key_binding = is_string($key) ? ":$key" : $key + 1;
                $pdo_statement->bindValue($key_binding, $value, $param);
            }
        } else {
            // Positional placeholders
            $i = 1;
            foreach ($bindings as $value) {
                $param = PDO::PARAM_STR;
                if (is_array($value) || is_object($value)) {
                    $value = json_encode($value);
                } elseif (is_null($value) || strtolower((string) $value) === 'null') {
                    $param = PDO::PARAM_NULL;
                } elseif (is_int($value)) {
                    $param = PDO::PARAM_INT;
                } elseif (is_resource($value)) {
                    $param = PDO::PARAM_LOB;
                } elseif (is_bool($value)) {
                    $param = PDO::PARAM_BOOL;
                } elseif (is_string($value)) {
                    $param = PDO::PARAM_STR;
                }
                $pdo_statement->bindValue($i, $value, $param);
                $i++;
            }
        }
    }

    /**
     * Data trainer. key => :value
     *
     * @param  array $data
     * @param  bool  $byKey
     * @return array
     */
    private function add2points(array $data, bool $byKey = false): array
    {
        $result = [];

        if (!$byKey) {
            foreach ($data as $key => $value) {
                $result[$value] = ':' . $value;
            }
            return $result;
        }

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $result[$key] = ':' . $value;
            } else {
                $result[$key] = '?';
            }
        }

        return $result;
    }

    /**
     * Min
     *
     * @param  string $column
     * @return int|float
     */
    public function min($column): int|float
    {
        return $this->aggregate('min', $column);
    }

    /**
     * Avg
     *
     * @param  string $column
     * @return int|float
     */
    public function avg($column): int|float
    {
        return $this->aggregate('avg', $column);
    }

    /**
     * Sum
     *
     * @param  string $column
     * @return int|float
     */
    public function sum($column): int|float
    {
        return $this->aggregate('sum', $column);
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
     * Count
     *
     * @param  string $column
     * @return int
     */
    public function count($column = '*')
    {
        return $this->aggregate('count', $column);
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
     * Lock the selected rows for update
     *
     * @return QueryBuilder
     */
    public function lockForUpdate(): QueryBuilder
    {
        $this->lock_for_update = true;

        return $this;
    }

    /**
     * Lock the selected rows in share mode
     *
     * @return QueryBuilder
     */
    public function sharedLock(): QueryBuilder
    {
        $this->shared_lock = true;

        return $this;
    }

    /**
     * Take = Limit
     *
     * @param  int $limit
     * @return QueryBuilder
     */
    public function take(int $limit): QueryBuilder
    {
        if (is_null($this->limit)) {
            $this->limit = 'limit ' . $limit;

            return $this;
        }

        if ($this->adapter === 'pgsql') {
            $this->limit = $this->limit . ' limit ' . $limit;
        } elseif (preg_match('/^([\d]+),\s$/', $this->limit, $match)) {
            $this->limit = 'limit ' . end($match) . ', ' . $limit;
        }

        return $this;
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

        $statement = $this->execute($sql, $this->where_data_binding, false);

        $data = $statement->fetchAll();

        $statement->closeCursor();

        $this->where_data_binding = [];

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
     * Add select column.
     *
     * SELECT $column | SELECT column1, column2, ...
     *
     * @param  array $select
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
     * Jump = Offset
     *
     * @param  int $offset
     * @return QueryBuilder
     */
    public function jump(int $offset = 0): QueryBuilder
    {
        // Check the limit value definition
        if (is_null($this->limit) || strlen(trim($this->limit)) === 0) {
            if ($this->adapter === "pgsql") {
                $this->limit = 'offset ' . $offset;
            } else {
                $this->limit = $offset . ', ';
            }
        }

        return $this;
    }

    /**
     * Update action
     *
     * @param  array $data
     * @return int
     */
    public function update(array $data = []): int
    {
        $sql = 'update ' . $this->table . ' set ';
        $sql .= implode(' = ?, ', array_keys($data)) . ' = ?';

        if (!is_null($this->where)) {
            $sql .= ' where ' . $this->where;

            $this->where = null;
        }

        $this->where_data_binding = array_merge(array_values($data), $this->where_data_binding);

        $statement = $this->execute($sql, $this->where_data_binding);

        $result = $statement->rowCount();

        $this->where_data_binding = [];

        return (int) $result;
    }

    /**
     * Remove simplified stream from delete.
     *
     * @param  string $column
     * @param  mixed  $comparator
     * @param  string $value
     * @return int
     * @throws QueryBuilderException
     */
    public function remove(string $column, mixed $comparator = '=', $value = null): int
    {
        $this->where = null;

        return $this->where($column, $comparator, $value)->delete();
    }

    /**
     * Delete row on table
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

        $statement = $this->execute($sql, $this->where_data_binding);

        $result = $statement->rowCount();

        $this->where_data_binding = [];

        return (int) $result;
    }

    /**
     * Increment column
     *
     * @param string $column
     * @param int    $step
     *
     * @return int
     */
    public function increment(string $column, int $step = 1): int
    {
        return $this->incrementAction($column, $step);
    }

    /**
     * Decrement column
     *
     * @param  string $column
     * @param  int    $step
     * @return int
     */
    public function decrement(string $column, int $step = 1): int
    {
        return $this->incrementAction($column, $step, '-');
    }

    /**
     * Method to customize the increment and decrement methods
     *
     * @param  string $column
     * @param  int    $step
     * @param  string $direction
     * @return int
     */
    private function incrementAction(string $column, int $step = 1, string $direction = '+')
    {
        $sql = 'update ' . $this->table . ' set ' . $column . ' = ' . $column . ' ' . $direction . ' ' . $step;

        if (!is_null($this->where)) {
            $sql .= ' where ' . $this->where;

            $this->where = null;
        }

        $statement = $this->execute($sql, $this->where_data_binding);

        return (int)$statement->rowCount();
    }

    /**
     * Allows a query with the DISTINCT clause
     *
     * This method modifies the SELECT statement to include the DISTINCT keyword,
     * ensuring that the results returned are unique for the specified column.
     *
     * @param  string $column The column to apply the DISTINCT clause on.
     * @return QueryBuilder Returns the current QueryBuilder instance.
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
     * Truncate table
     *
     * This method will remove all rows from the table without logging the individual row deletions.
     * It is faster than the DELETE statement because it does not generate individual row delete actions.
     * However, it cannot be rolled back if the database is not in a transaction.
     *
     * @return bool
     */
    public function truncate(): bool
    {
        $connection = $this->writeConnection();

        if ($connection->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite') {
            $sql = 'delete from ' . $this->table . ';';
            if (!$connection->inTransaction()) {
                $sql .= ' VACUUM;';
            }
        } else {
            $sql = 'truncate table ' . $this->table . ';';
        }

        $this->last_query = $sql;

        $start_at = microtime(true);
        $result = (bool) $connection->exec($sql);
        $ended_at = microtime(true);

        $this->triggerQueryEvent($sql, $ended_at - $start_at);

        $this->last_query = $sql;

        return $result;
    }

    /**
     * InsertAndGetLastId action launches the insert and lastInsertId actions
     *
     * @param  array $values
     * @return string|int|bool
     */
    public function insertAndGetLastId(array $values): string|int|bool
    {
        $this->insert($values);

        $result = $this->writeConnection()->lastInsertId();

        return is_numeric($result) ? (int)$result : $result;
    }

    /**
     * Insert
     *
     * The data to be inserted into the database.
     *
     * @param  array $values
     * @return int
     */
    public function insert(array $values): int
    {
        $mixture_item_structure_detected = false;
        $single_item_structure_detected = false;

        $single_item_structure = [];
        $multi_item_structures = [];

        foreach ($values as $key => $value) {
            if (is_array($value) && is_int($key)) {
                $multi_item_structures[] = $value;
                $mixture_item_structure_detected = true;
            } else {
                $single_item_structure[$key] = $value;
                $single_item_structure_detected = true;
            }
        }

        if ($single_item_structure_detected && $mixture_item_structure_detected) {
            throw new QueryBuilderException(
                'Mixed structure detected in insert data. Cannot mix single and multiple row inserts.',
            );
        }

        $multi_item_structures = !empty($multi_item_structures)
            ? $multi_item_structures
            : [$single_item_structure];

        $row_affected = 0;

        foreach ($multi_item_structures as $structure) {
            $row_affected += $this->insertOne($structure);
        }

        return $row_affected;
    }

    /**
     * Insert On, insert one row in the table
     *
     * @param  array $values
     * @return int
     * @see    insert
     */
    private function insertOne(array $values): int
    {
        $fields = array_keys($values);
        $column = implode(', ', $fields);

        $sql = 'insert into ' . $this->table . '(' . $column . ') values';

        $sql .= '(' . implode(', ', $this->add2points($fields, true)) . ');';

        $statement = $this->execute($sql, $values);

        return (int) $statement->rowCount();
    }

    /**
     * Execute statement
     *
     * @param string $sql
     * @param array $bindings
     * @param bool $write Whether the statement mutates data (routes to the primary).
     * @return PDOStatement
     */
    private function execute(string $sql, array $bindings = [], bool $write = true): PDOStatement
    {
        $this->last_query = $sql;

        $connection = $write ? $this->writeConnection() : $this->readConnection();

        $statement = $connection->prepare($sql);

        $this->bind($statement, $bindings);

        try {
            $start_at = microtime(true);
            $statement->execute();
            $ended_at = microtime(true);

            $this->triggerQueryEvent($sql, $ended_at - $start_at, $bindings);
        } catch (\Exception $e) {
            throw new QueryBuilderException(
                'message: ' . $e->getMessage() . '; query: ' . $this->last_query,
                $this->last_query,
                E_ERROR,
            );
        }

        return $statement;
    }

    /**
     * Drop, remove the table
     *
     * @return mixed
     */
    public function drop(): bool
    {
        $sql = 'drop table ' . $this->table;

        $this->last_query = $sql;

        $start_at = microtime(true);
        $result = (bool) $this->writeConnection()->exec($sql);
        $ended_at = microtime(true);

        $this->triggerQueryEvent($sql, $ended_at - $start_at);

        return $result;
    }

    /**
     * Paginate, make pagination system
     *
     * @param  int $per_page
     * @param  int $current
     * @param  int $chunk
     * @return Pagination
     */
    public function paginate(int $per_page, int $current = 0, ?int $chunk = null): Pagination
    {
        // We go to back page
        --$current;

        // Variable containing the number of jump. $jump;
        if ($current <= 0) {
            $jump = 0;
            $current = 1;
        } else {
            $jump = $per_page * $current;
            $current++;
        }

        // Saving information about current query
        $where = $this->where;
        $join = $this->join;
        $data_bind = $this->where_data_binding;

        $data = $this->jump($jump)->take($per_page)->get();

        if (is_array($data)) {
            $data = collect($data);
        }

        // Reinitialisation of current query
        $this->where = $where;
        $this->join = $join;
        $this->where_data_binding = $data_bind;

        // We count the number of pages that remain
        $total = $this->count();
        $total_of_page = (int) ceil($total / $per_page);
        $rest_of_page = $total_of_page - $current;

        // Grouped data
        if (is_int($chunk)) {
            $data = $data->chunk($chunk);
        }

        // Enables automatic paging.
        return new Pagination(
            next: $current >= 1 && $rest_of_page > 0 ? $current + 1 : 0,
            previous: ($current - 1) <= 0 ? 1 : ($current - 1),
            total: $total,
            perPage: $per_page,
            current: $current,
            data: $data,
        );
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
        return $this->writeConnection()->lastInsertId($name);
    }

    /**
     * JsonSerialize implementation
     *
     * @see    httsp://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return string
     */
    public function jsonSerialize(): mixed
    {
        return json_encode($this->get());
    }

    /**
     * Define the data to associate
     *
     * @param  array $data_binding
     * @return void
     */
    public function setWhereDataBinding(array $data_binding): void
    {
        $this->where_data_binding = $data_binding;
    }

    /**
     * Trigger the query event
     *
     * @param  string $sql
     * @param  float $execution_time
     * @param  array  $bindings
     * @return void
     */
    private function triggerQueryEvent(string $sql, float $execution_time = 0, array $bindings = []): void
    {
        Database::triggerQueryEvent($sql, $execution_time, $bindings);
    }

    /**
     * Transformation automatically the result to JSON
     *
     * @param  int $option
     * @return string
     */
    public function toJson(int $option = 0): string
    {
        return json_encode($this->get(), $option);
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
}
