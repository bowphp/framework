<?php

namespace Bow\Database\Migration;

use Bow\Support\Str;

class Schema
{
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
		$blueprint = new Blueprint($table, $displaySql);
		call_user_func_array($cb, [$blueprint]);
		if (statement((string) $blueprint)) {
			echo "\033[0;32m$table table created.\033[00m\n";
		} else {
            echo "\033[0;31m$table table already exists or not created.\033[00m\n";
        }
	}
}