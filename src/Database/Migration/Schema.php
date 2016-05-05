<?php

namespace Bow\Database\Migration;

use Bow\Support\Str;
use Bow\Database\Database;
use Bow\Database\Migration\AlterTable;

class Schema
{
    /**
     * @var string
     */
    private static $table;

    /**
     * @var Blueprint
     */
    private static $blueprint;

    /**
     * @var array
     */
    private static $data;

    /**
     * @var string
     */
    private $fill;

	/**
	 * @param string $table
	 * @return int
	 */
	public static function drop($table)
	{
        if (statement("drop table $table;")) {
            echo "\033[0;32m$table table droped.\033[00m\n";
        } else {
            echo "\033[0;31m$table table not exists.\033[00m\n";
        }
	}

	/**
     * fonction de creation d'une nouvelle table dans la base de donnée.
     *
	 * @param string $table
	 * @param callable $cb
     * @param bool $displaySql
	 */
	public static function create($table, Callable $cb, $displaySql = false)
	{
        static::$table = $table;

		$columnsMaker = new TableColumnsMaker($table, $displaySql);
		call_user_func_array($cb, [$columnsMaker]);

        $sql = (new Blueprint($columnsMaker))->toCreateTableStatement();
        self::$data = $columnsMaker->getBindData();

		if (statement($sql)) {
			echo "\033[0;32m$table table created.\033[00m\n";
		} else {
            echo "\033[0;31m$table table already exists or not created.\033[00m\n";
        }
	}

	/**
	 * @param string $table
	 * @param bool $displaySql
	 * @param \Closure $cb
	 */
	public static function table($table, \Closure $cb, $displaySql = false)
	{
        $alter = new AlterTable($table, $displaySql);
        $cb($alter);
	}

    /**
     * fillTable, remplir un table pour permet le developpement.
     *
     * @param int $n
     *
     * @return mixed
     */
    public static function fillTable($n = 1)
    {
        $insertArray = [];

        for($i = 0; $i < $n; $i++) {
            $insertArray[] = static::$data;
        }

        return Database::table(static::$table)->insert($insertArray);
    }
}