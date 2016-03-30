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
		return statement("drop table $table;");
	}

	/**
	 * @param string $table
	 * @param callable $cb
	 */
	public static function create($table, Callable $cb)
	{
		$blueprint = new Blueprint();
		call_user_func_array($cb, [$blueprint]);
		$sql = Str::replace(":table:", $table, $blueprint);
		statement($sql);
	}
}