<?php

namespace Bow\Database;

use Bow\Support\Collection;
use Bow\Exception\ModelException;

class Schema
{

	/**
	 * __invoke
	 * @param \PDO $db
	 * @return bool
	 */
	public function __callStatic(\PDO $db)
	{
		$statement = $this->stringify();
		if (is_string($statement)) {
			$status = $db->exec($statement);
		} else {
			$status = false;
		}

		return $status;
	}

	public static function drop()
	{

	}

	public static function create()
	{

	}
}