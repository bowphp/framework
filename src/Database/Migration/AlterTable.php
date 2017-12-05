<?php
namespace Bow\Database\Migration;

use Bow\Database\Database;

class AlterTable
{
    /**
     * @var string
     */
    private $tableName;

    /**
     * @var bool|false
     */
    private $displaySql;

    /**
     * Contructeur.
     *
     * @param string     $tableName
     * @param bool|false $displaySql
     */
    public function __construct($tableName, $displaySql = false)
    {
        $this->tableName  = $tableName;
        $this->displaySql = $displaySql;
    }

    /**
     * Ajout une ou plusieur colonne dans la description de la table.
     *
     * @param callable $cb
     */
    public function add(callable $cb)
    {
        $columns = new TablePrinter($this->tableName, $this->displaySql);

        $cb($columns);

        $sql = (new Statement($columns))->makeAlterTableStatement();

        if ($this->displaySql) {
            echo $sql . "\n";
        }

        if (Database::statement($sql)) {
            echo "\033[0;32m" . $this->tableName . " table updated.\033[00m\n";
        } else {
            echo "\033[0;31mAll or one columns already exists.\033[00m\n";
        }
    }

    /**
     * Supprime des columns dans la description de la table.
     *
     * @param array $col
     */
    public function drop(array $col)
    {
        $columns = "";

        foreach ($col as $key => $value) {
            if ($key > 0) {
                $columns .= ", ";
            }

            $columns .= "DROP `$value`";
        }

        $sql = "ALTER TABLE ". $this->tableName . " " . $columns . ";";

        if (Database::statement($sql)) {
            echo "\033[0;32m'" . implode(", ", func_get_args()) . "' in " . $this->tableName . " table have been droped.\033[00m\n";
        } else {
            echo "\033[0;31m'" . implode(", ", func_get_args()) . "' not exists in " . $this->tableName . " table.\033[00m\n";
        }

        if ($this->displaySql) {
            echo $sql . "\n";
        }
    }

    /**
     * Change le nom d'un table.
     *
     * @param string $newTableName Le nom de la nouveau table
     */
    public function change($newTableName)
    {
        if (Database::statement("RENAME TABLE " . $this->tableName . " TO " . $newTableName)) {
            echo "\033[0;32mTabe renamed.\033[00m\n";
        } else {
            echo "\033[0;31m Cannot rename table.\033[00m\n";
        }
    }
}
