<?php
namespace Bow\Database\Migration;

use Bow\Database\Database;

class Schema
{
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
     * Supprimer une table.
     *
     * @param string $table
     */
    public static function dropIfExists($table)
    {
        $table = Database::getConnectionAdapter()->getTablePrefix().$table;

        if ((bool) Database::statement('DROP TABLE IF EXISTS ' . $table . ';')) {
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
        $adapter = Database::getConnectionAdapter();
        $table = $adapter->getTablePrefix().$table;

        $fields = new TablePrinter($table);
        call_user_func_array($cb, [$fields]);

        $charset =$adapter->getCharset();

        $fields->charset($charset);
        $statement = new Statement($fields);

        if ($adapter->getName() == 'mysql') {
            $sql = $statement->makeMysqlCreateTableStatement();
        } else {
            $sql = $statement->makeSqliteCreateTableStatement();
        }

        if ($sql == null) {
            die("\033[0;31mSVP vérifiez votre methode 'up'.\033[00m\n");
        }

        if ($displaySql) {
            echo $sql . "\n";
        }

        if (Database::statement($sql)) {
            echo "\033[0;32mLa table $table a été créer.\033[00m\n";
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