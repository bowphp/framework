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

		$columnsMaker = new ColumnsMaker($table, $displaySql);
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
	 * @param Callable $cb
	 */
	public static function table($table, Callable $cb, $displaySql = false)
	{
        $alter = new AlterTable($table, $displaySql);
        $cb($alter);
	}

    /**
     * fillTable, remplir un table pour permet le developpement.
     *
     *
     * @param string|array $table [optional]
     * @param array $marks
     * @param int $n
     *
     * @return mixed
     */
    public static function fillTable($table, array $marks = null, $n = 1)
    {
        if (is_int($table)) {
            $n = $table;
            $table = null;
            $marks = [];
        }

        if (is_array($table)) {
            $marks = $table;
            $table = null;
            $n = 1;
        }

        if (is_int($marks)) {
            $n = $marks;
            $marks = null;
        }

        if (!is_string($table)) {
            $table = static::$table;
        }

        if ($marks) {
            $data = static::parseMarks($marks);
        } else {
            $data = static::$data;
        }

        $insertArray = [];

        for($i = 0; $i < $n; $i++) {
            $insertArray[] = $data;
        }

        return Database::table($table)->insert($insertArray);
    }

    /**
     * ParseMarks.
     * ----------------------------------
     * field name|  types   | size      |
     * ----------------------------------
     * name      |i, s, d, t| 1 (string)|
     * ----------------------------------
     *
     * eg: name|s:59 => name string length 59
     * eg: name|s:100;age|i
     *
     * @param string $marks
     * @return array
     */
    private static function parseMarks($marks)
    {
        $r = [];

        if (is_string($marks)) {
            $parts = explode(";", $marks);
            $types = [
                "i" => "number",
                "s" => "string",
                "d" => "date",
                "t" => "current_timestamp"
            ];

            foreach($parts as $key => $values) {
                $subPart = explode("|", $values);
                $typeAndLength = explode(":", $subPart[1]);
                $key = $subPart[0];
                $type = $types[Str::lower($typeAndLength[0])];
                $data = Filler::${$type};

                if (count($typeAndLength) == 2) {
                    if ($type == "string") {
                        $r[$key] = Str::slice($data, 0, $typeAndLength[1]);
                    } else if ($type == "integer") {
                        $r[$key] = $typeAndLength[1];
                    } else {
                        $r[$key] = $data;
                    }
                } else {
                    $r[$key] = $data;
                }
            }

            return $r;
        }

        if (is_array($marks)) {
            return $r;
        }
    }
}