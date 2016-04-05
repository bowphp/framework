<?php

namespace Bow\Database\Migration;

use Bow\Database\Database;
use Bow\Support\Str;

class Schema
{
    /**
     * @var string
     */
    private static $table;

    /**
     * @var string
     */
    private static $cb;

    /**
     * @var Blueprint
     */
    private static $blueprint;

    /**
     * @var Blueprint
     */
    private static $dataSql;

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
	 * @param string $table
	 * @param callable $cb
     * @param bool $displaySql
	 */
	public static function create($table, Callable $cb, $displaySql = false)
	{
        static::$table = $table;
        static::$cb    = $cb;

		$blueprint = new Blueprint($table, $displaySql);
		call_user_func_array($cb, [$blueprint]);

        static::$dataSql = explode("[::]", (string) $blueprint, 2);

		if (statement(static::$dataSql[0])) {
			echo "\033[0;32m$table table created.\033[00m\n";
		} else {
            echo "\033[0;31m$table table already exists or not created.\033[00m\n";
        }
	}

	/**
	 * @param string $table
	 * @param \Closure $cb
	 */
	public static function table($table, \Closure $cb)
	{

	}

    /**
     * fillTable remplir un table pour permet le developpement.
     */
    public static function fillTable()
    {
        Database::table(static::$table)->insert(unserialize( static::$dataSql[1]));
    }
}