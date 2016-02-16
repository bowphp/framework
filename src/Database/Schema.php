<?php

namespace Bow\Database;

use Bow\Support\Collection;
use Bow\Exception\ModelException;

class Schema
{
	/**
	 * fields list
	 * 
	 * @var bool
	 */
	private $fileds = null;
	/**
	 * define all avalaibles method assign to Model
	 * 
	 * @var bool
	 */
	private static $types = [ "varchar", "int", "char", "date", "datetime", "text", "timestamp", "bigint", "longint" ];
	/**
	 * define the primary key
	 * 
	 * @var bool
	 */
	private $primary = null;
	/**
	 * last define field
	 * 
	 * @var bool
	 */
	private $lastField = null;
	/**
	 * Table name
	 * 
	 * @var bool
	 */
	private $table = null;
	/**
	 * Sql Statement
	 * 
	 * @var string
	 */ 
	private $sqlStement = null;
	/**
	 * @var string
	 */
	private $engine = "MyIsam";
	/**
	 * @var string
	 */
	private $character = "UTF8";
	/**
	 * define the auto increment field
	 * @var bool
	 */
	private $autoincrement = null;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->fields  = new Collection;

		return $this;
	}

	/**
	 * setTableName, set the model table name
	 * @param $table
	 */
	public function setTableName($table)
	{
		$this->table = $table;
	}

	/**
	 * setEngine, set the model engine name
	 * @param $character
	 */
	public function setCharacter($character)
	{
		$this->character = $character;
	}

	/**
	 * int
	 * 
	 * @param string $field
	 * @param int $size
	 * @param bool $null
	 * @param null|string $default
	 * 
	 * @return $this
	 */
	public function int($field, $size = 11, $null = false, $default = null)
	{
		return $this->loadWhole("int", $field, $size, $null, $default);
	}

	/**
	 * bigint
	 * 
	 * @param string $field
	 * @param int $size
	 * @param bool $null
	 * @param null|string $default
	 * 
	 * @return $this
	 */
	public function bigint($field, $size = 20, $null = false, $default = null)
	{
		return $this->loadWhole("bigint", $field, $size, $null, $default);
	}

	/**
	 * varchar
	 * 
	 * @param string $field
	 * @param int $size
	 * @param bool $null
	 * @param null|string $default
	 * 
	 * @return $this
	 */
	public function varchar($field, $size = 255, $null = false, $default = null)
	{
		if ($size > 255) {
			throw new Exception("Error Processing Request", 1);
		}

		return $this->loadWhole("varchar", $field, $size, $null, $default);
	}

	/**
	 * date
	 * 
	 * @param string $field
	 * @param bool $null
	 * @param bool $default
	 * 
	 * @return $this
	 */ 
	public function date($field, $null = false)
	{
		$this->addField("date", $field, [
				"null" => $null
			]);

		return $this;
	}

	/**
	 * datetime
	 * 
	 * @param string $field
	 * @param string|null $null
	 * 
	 * @param $this
	 */ 
	public function datetime($field, $null = false)
	{
		$this->addField("datetime", $field, [
				"null" => $null
			]);

		return $this;
	}

	/**
	 * timestamp
	 * 
	 * @param string $field
	 * @param string|null $null
	 * 
	 * @param $this
	 */ 
	public function timestamp($field, $null = false)
	{
		$this->addField("timestamp", $field, [
				"null" => $null
			]);

		return $this;
	}

	/**
	 * longint
	 * 
	 * @param string $field
	 * @param int $size
	 * @param bool $null
	 * @param null|string $default
	 * 
	 * @return $this
	 */
	public function longint($field, $size = 20, $null = false, $default = null)
	{
		return $this->loadWhole("longint", $field, $size, $null, $default);
	}

	/**
	 * text
	 * 
	 * @param string $field
	 * @param bool $null
	 * 
	 * @param $this
	 */ 
	public function text($field, $null = false)
	{
		$this->addField("text", $field, [
				"null" => $null
			]);
		
		return $this;
	}

	/**
	 * char
	 * 
	 * @param string $field
	 * @param bool $null
	 * @param string|null $default
	 * 
	 * @param $this
	 */ 
	public function char($field, $size = 1, $null = false, $default = null)
	{
		if ($size > 4294967295) {
			throw new ModelException("Char, max size is 4294967295", 1);
		}

		return $this->loadWhole("char", $field, $size, $null, $default);
	}

	/**
	 * autoincrement
	 * 
	 * @return $this
	 */ 
	public function autoincrement($filed = null)
	{
		if ($this->autoincrement === null) {
			if ($this->lastField !== null) {
				if (in_array($this->lastField->method, ["int", "longint", "bigint"])) {
					$this->autoincrement = $this->lastField;
				} else {
					throw new ModelException("Cannot add autoincrement to " . $this->lastField->method, 1);
				}
			} else {
				if ($filed) {
					$this->int($filed);
				}
			
			}
		}

		return $this;
	}

	/**
	 * primary
	 * 
	 * @return $this
	 */
	public function primary()
	{
		if ($this->primary === null) {
			return $this->addIndexes("primary");
		} else {
			throw new ModelException("Primary key has already defined", 1);
		}
	}

	/**
	 * indexe
	 * 
	 * @return $this
	 */
	public function indexe($field = null)
	{
		return $this->addIndexes("indexe");
	}

	/**
	 * unique
	 * 
	 * @return $this
	 */
	public function unique()
	{
		return $this->addIndexes("unique");
	}

	/**
	 * addIndexes
	 * 
	 * @param string $indexType
	 * 
	 * @return
	 */
	private function addIndexes($indexType)
	{
		if ($this->lastField !== null) {
			$last = $this->lastField;
			$this->fields->get($last->method)->update($last->field, [$indexType => true]);
		} else {
			throw new ModelException("Cannot assign {$indexType}. Because field are not defined.", 1);
		}

		return $this;
	}

	/**
	 * addField
	 * 
	 * @return $this
	 */
	private function addField($method, $field, $data)
	{
		if (!method_exists($this, $method)) {
			throw new Exception("Error Processing Request", 1);
		}

		if (!$this->fields->has($method)) {
			$this->fields->add($method, new Collection);
		}

		if (!$this->fields->get($method)->has($field)) {
			// default index are at false
			$data["primary"] = false;
			$data["unique"] = false;
			$data["indexe"] = false;
			$this->fields->get($method)->add($field, $data);
			$this->lastField = (object) ["method" => $method, "field" => $field];
		}

		return $this;
	}

	/**
	 * loadWhole
	 * 
	 * @param string $method
	 * @param string $field
	 * @param int $size
	 * @param bool $null
	 * @param null|string $default
	 * 
	 * @return $this
	 */
	private function loadWhole($method, $field, $size = 20, $null = false, $default = null)
	{
		if (is_bool($size)) {
			$null = $size;
			$size = 11;
		} else {
			if (is_string($size)) {
				$default = $size;
				$size = 11;
				$null = false;
			} else {
				if (is_string($null)) {
					$default = $null;
					$null = false;
				}
			}
		}

		$this->addField($method, $field, [
				"size" => $size,
				"null" => $null,
				"default" => $default
			]);

		return $this;
	}

	/**
	 * stringify
	 * 
	 * @return string
	 */
	private function stringify()
	{
		$this->fields->each(function ($type, Collection $value) {

			switch ($type) {
				case 'varchar':
				case 'char':
					$value->each(function($info, $field) use ($type) {
						$info = (object) $info;
						$null = $this->getNullType($info->null);

						$this->sqlStement .= " `$field` $type(" . $info->size .") $null";

						if ($info->default) {
							$this->sqlStement .= " default '" . $info->default . "'";
						}

						if ($info->primary === null) {
							$this->sqlStement .= ", primary key(`$field`)";
							$info->primary = null;
						} else {
							if ($info->unique === null) {
								$this->sqlStement .= " unique";
							}
						}
					});
					break;

				case "int":
				case "bigint":
				case "logint":
					$value->each(function ($info, $field) use ($type) {
						$info = (object) $info;
						$null = $this->getNullType($info->null);
						$this->sqlStement .= "`$field` $type($info->size) $null";
						
						if ($info->default) {
							$this->sqlStement .= " default " . $info->default;
						}

						if ($this->autoincrement !== null) {
							if ($this->autoincrement->method == $type
								&& $this->autoincrement->field == $field) {
								$this->sqlStement .= " auto_increment";
							}
							$this->autoincrement = null;
						}

						if ($info->primary) {
							$this->sqlStement .= ", primary key (`$field`)";
							$info->primary = null;
						} else {
							if ($info->unique) {
								$this->sqlStement .= " unique";
							}
						}
					});
					break;
					
				case "date":
				case "datetime":
					$value->each(function($info, $field) use ($type){
						$info = (object) $info;
						$null = $this->getNullType($info->null);
						$this->sqlStement .= " `$field` $type $null";

						if ($info->primary) {
							$this->sqlStement .= ", primary key (`$field`)";
							$info->primary = null;
						} else {
							if ($info->unique) {
								$this->sqlStement .= " unique";
							}
						}
					});
					break;

				case "timestamp":
					$value->each(function($info, $field) use ($type){
						$info = (object) $info;
						$null = $this->getNullType($info->null);
						$this->sqlStement .= " `$field` $type $null";

						if ($info->primary) {
							$this->sqlStement .= ", primary key (`$field`)";
							$info->primary = null;
						} else {
							if ($info->unique) {
								$this->sqlStement .= " unique";
							}
						}
					});
					break;
			}

		});
		
		if ($this->sqlStement !== null) {
			return "create table `". $this->table ."`($this->sqlStement)engine=" . $this->engine . " default charset=" . $this->character .";";
		}

		return null;
	}

	/**
	 * getNullType retourne les valeurs "null" ou "not null"
	 * 
	 * @param bool $null
	 * @return string
	 */
	private function getNullType($null)
	{
		if ($this->sqlStement != null) {
			$this->sqlStement .= ", ";
		}
		
		$nullType = "not null";
		
		if ($null) {
			$nullType = "null";
		}	

		return $nullType;
	}

	/**
	 * __invoke
	 * 
	 * @return bool
	 */
	public function __invoke(\PDO $db)
	{
		$statement = $this->stringify();
		if (is_string($statement)) {
			$status = $db->exec($statement);
		} else {
			$status = false;
		}

		return $status;
	}
}