<?php


namespace System\Database;

use Closure;
use System\Database\Model;

class Schema
{
	private static $int;

	private static $str;

	public static function create($modelName, Closure $cb)
	{	
		$m = new Model($modelName);
		call_user_func_array($cb, [$m]);
		$m->create();
	}
}