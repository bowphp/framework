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
	 */
	public static function create($table, Callable $cb)
	{
		$blueprint = new Blueprint();
		call_user_func_array($cb, [$blueprint]);
		$sql = Str::replace(":table:", $table, (string) $blueprint);
		if (statement($sql)) {
			echo "\033[0;32m$table table created.\033[00m\n";
		} else {
			echo "\033[0;31m$table table already exists.\033[00m\n";
		}
	}
}