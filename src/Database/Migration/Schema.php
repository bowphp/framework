<?php
namespace Bow\Database\Migration;

use Bow\Database\Database;

class Schema
{
    /**
     * @var string
     */
    private static $table;

    /**
     * @var array
     */
    private static $data;

    /**
     * Supprimer une table.
     *
     * @param string $table
     */
    public static function drop($table)
    {
        $table = Database::getConnectionAdapter()->getTablePrefix().$table;

        if ((bool) Database::statement('DROP TABLE ' . $table . ';')) {
            echo "\033[0;32m$table table droped.\033[00m\n";
        } else {
            echo "\033[0;31m$table table not exists.\033[00m\n";
        }
    }

    /**
     * Fonction de creation d'une nouvelle table dans la base de donnée.
     *
     * @param string $table
     * @param callable $cb
     * @param bool $displaySql
     */
    public static function create($table, Callable $cb, $displaySql = false)
    {
        $table = Database::getConnectionAdapter()->getTablePrefix().$table;

        $fields = new Fields($table);
        call_user_func_array($cb, [$fields]);

        $sql = (new Statement($fields))->makeCreateTableStatement();

        if ($sql == null) {
            die("\033[0;31mPlease check your 'up' method.\033[00m\n");
        }

        if ($displaySql) {
            echo $sql . "\n";
        }

        static::$data = $fields->getBindData();

        if (Database::statement($sql)) {
            echo "\033[0;32m$table table created.\033[00m\n";
        }
    }

    /**
     * Manipule les informations de la table.
     *
     * @param string $table
     * @param bool $displaySql
     * @param Callable $cb
     */
    public static function table($table, Callable $cb, $displaySql = false)
    {
        $table = Database::getConnectionAdapter()->getTablePrefix().$table;
        call_user_func_array($cb, [new AlterTable($table, $displaySql)]);
    }
}