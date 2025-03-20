# Bow Database

Bow Framework's database system is very simple database manager api with support:

- MySQL
- PostGreSQL
- SQLite

Make database connexion is very simple

```php
use Bow\Database\Database;

// Configure the database
Database::configure([
    "fetch" => PDO::FETCH_OBJ,
    "default" => "mysql",
    "connection" => [
        "mysql" => [
            "driver" => "mysql",
            "hostname" => getenv("MYSQL_HOSTNAME"),
            "username" => getenv("MYSQL_USER"),
            "password" => getenv("MYSQL_PASSWORD"),
            "database" => getenv("MYSQL_DATABASE"),
            "charset"  => getenv("MYSQL_CHARSET"),
            "collation" => getenv("MYSQL_COLLATE") ? getenv("MYSQL_COLLATE") : "utf8_unicode_ci",
            "port" => 3306,
            "socket" => null
        ],
        "sqlite" => [
            "driver" => "sqlite",
            "database" => ":memory:",
            "prefix" => "table_prefix"
        ],
        'pgsql' => [
            'driver' => 'pgsql',
            'hostname' => app_env('DB_HOSTNAME', 'localhost'),
            'username' => app_env('DB_USERNAME', 'test'),
            'password' => app_env('DB_PASSWORD', 'test'),
            'database' => app_env('DB_DBNAME', 'test'),
            'charset'  => app_env('DB_CHARSET', 'utf8'),
            'prefix' => app_env('DB_PREFIX', ''),
            'port' => app_env('DB_PORT', 3306)
        ],
    ]
]);
```

Let's show a little example:

```php
use Bow\Database\Database;

$users = Database::select("select * from users");
```

From model example:

```php
use App\Models\User as UserModel;

$users = UserModel::all();
```

## Diagramme de séquence

```mermaid
sequenceDiagram
    participant App as Application
    participant DB as Database
    participant Adapter as DatabaseAdapter
    participant Query as QueryBuilder
    participant PDO as PDO Connection
    participant DB_Server as Database Server

    Note over App,DB_Server: Configuration and Connection
    
    App->>DB: Database::configure(config)
    DB->>DB: getInstance()
    
    alt MySQL Connection
        DB->>Adapter: new MysqlAdapter(config)
    else PostgreSQL Connection
        DB->>Adapter: new PostgreSQLAdapter(config)
    else SQLite Connection
        DB->>Adapter: new SqliteAdapter(config)
    end
    
    Adapter->>PDO: new PDO(dsn, username, password)
    PDO-->>DB_Server: Establishes connection
    
    Note over App,DB_Server: Queries with Query Builder
    
    App->>DB: table('users')
    DB->>Query: new QueryBuilder('users', connection)
    Query->>Adapter: getInstance()
    
    alt Select Query
        App->>Query: where('id', 1)->get()
        Query->>Query: toSql()
        Query->>PDO: prepare(sql)
        PDO->>DB_Server: Execute Query
        DB_Server-->>App: Results
    else Insert Query
        App->>Query: insert(['name' => 'John'])
        Query->>PDO: prepare(sql)
        PDO->>DB_Server: Execute Query
        DB_Server-->>App: Affected Rows
    end
```

Is very enjoyful api
