<?php

namespace Bow\Database\Migration;

use Bow\Support\Collection;
use Bow\Exception\ModelException;

class Blueprint
{
    /**
     * fields list
     *
     * @var Collection
     */
    private $fields;

    /**
     * define the primary key
     *
     * @var bool
     */
    private $primary = null;

    /**
     * last define field
     *
     * @var \StdClass
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
     * @var \StdClass
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
    public function integer($field, $size = 11, $null = false, $default = null)
    {
        return $this->loadWhole("integer", $field, $size, $null, $default);
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
    public function bigInteger($field, $size = 20, $null = false, $default = null)
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
     * @throws \Exception
     * @return $this
     */
    public function string($field, $size = 255, $null = false, $default = null)
    {
        $type = "varchar";
        if ($size > 255) {
            $type = "text";
        }

        return $this->loadWhole($type, $field, $size, $null, $default);
    }

    /**
     * date
     *
     * @param string $field
     * @param bool $null
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
     * @param string|bool $null
     *
     * @return Schema
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
     * @param string|bool $null
     *
     * @return Schema
     */
    public function timestamps($field, $null = false)
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
    public function longInteger($field, $size = 20, $null = false, $default = null)
    {
        return $this->loadWhole("longint", $field, $size, $null, $default);
    }

    /**
     * @param string $field
     * @param int $size
     * @param bool|false $null
     * @param string $default
     * @return Schema
     * @throws ModelException
     */
    public function character($field, $size = 1, $null = false, $default = null)
    {
        if ($size > 4294967295) {
            throw new ModelException("Max size is 4294967295", 1);
        }

        return $this->loadWhole("char", $field, $size, $null, $default);
    }

    /**
     * @param string $field
     * @param array $enums
     * @return Schema
     */
    public function enumerate($field, array $enums)
    {
        $this->addField("enum", $field, [
            "default" => $enums
        ]);
    }

    /**
     * autoincrement
     *
     * @param string $field
     * @throws ModelException
     * @return Schema
     */
    public function increment($field = null)
    {
        if ($this->autoincrement === null) {
            if ($this->lastField !== null) {
                if (in_array($this->lastField->method, ["int", "longint", "bigint"])) {
                    $this->autoincrement = $this->lastField;
                } else {
                    throw new ModelException("Cannot add autoincrement to " . $this->lastField->method, 1);
                }
            } else {
                if ($field) {
                    $this->integer($field)->primary();
                    $this->autoincrement = (object) [
                        "method" => "int",
                        "field" => $field
                    ];
                }
            }
        }

        return $this;
    }

    /**
     * primary
     *
     * @throws ModelException
     * @return $this
     */
    public function primary()
    {
        if ($this->primary === null) {
            return $this->addIndexes("primary");
        } else {
            throw new ModelException("Primary key has already defined", E_ERROR);
        }
    }

    /**
     * indexe
     *
     * @return Schema
     */
    public function indexe()
    {
        return $this->addIndexes("indexe");
    }

    /**
     * unique
     *
     * @return Schema
     */
    public function unique()
    {
        return $this->addIndexes("unique");
    }

    /**
     * addIndexes crée un clause index sur le champs spécifié.
     *
     * @param string $indexType
     * @throws ModelException
     * @return Schema
     */
    private function addIndexes($indexType)
    {
        if ($this->lastField !== null) {
            $last = $this->lastField;
            $this->fields->get($last->method)->update($last->field, [$indexType => true]);
        } else {
            throw new ModelException("Cannot assign {$indexType}. Because field are not defined.", E_ERROR);
        }

        return $this;
    }

    /**
     * addField
     *
     * @param string $method
     * @param string $field
     * @param string $data
     * @throws ModelException
     * @return $this
     */
    private function addField($method, $field, $data)
    {
        $method = strtolower($method);

        if (!$this->fields->has($method)) {
            $this->fields->add($method, new Collection);
        }

        if (!$this->fields->get($method)->has($field)) {
            // default index are at false
            $data["primary"] = false;
            $data["unique"]  = false;
            $data["indexe"]  = false;
            $this->fields->get($method)->add($field, $data);
            $this->lastField = (object) [
                "method" => $method,
                "field" => $field
            ];
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
                $size    = 11;
                $null    = false;
            } else {
                if (is_string($null)) {
                    $default = $null;
                    $null    = false;
                }
            }
        }

        $this->addField($method, $field, [
            "size"    => $size,
            "null"    => $null,
            "default" => $default
        ]);

        return $this;
    }

    /**
     * Ajout les indexes et la clé primaire.
     *
     * @param \StdClass $info
     * @param string $field
     */
    private function addIndexOrPrimaryKey($info, $field)
    {
        if ($info->primary !== null) {
            $this->sqlStement .= ", primary key(`$field`)";
            $info->primary = null;
        } else {
            if ($info->unique !== null) {
                $this->sqlStement .= " unique";
                $info->unique = null;
            }
        }
    }

    /**
     * Ajout les types de donnée au champ définir
     *
     * @param \StdClass $info
     * @param string $field
     * @param string $type
     */
    private function addFieldType($info, $field, $type)
    {
        $info = (object) $info;
        $null = $this->getNullType($info->null);
        $this->sqlStement .= "`$field` $type($info->size) $null";
    }

    /**
     * stringify
     *
     * @return string
     */
    private function stringify()
    {
        $this->fields->each(function (Collection $value, $type) {
            switch ($type) {
                case 'varchar':
                case 'char'   :
                case 'text'   :
                case "int"    :
                case "bigint" :
                case "longint":
                    $value->each(function ($info, $field) use ($type) {
                        $this->addFieldType($info, $field, $type);

                        if (in_array($type, ["int", "bigint", "longint"], true)) {
                            if ($this->autoincrement !== null) {
                                if ($this->autoincrement->method == $type && $this->autoincrement->field == $field) {
                                    $this->sqlStement .= " auto_increment";
                                }
                                $this->autoincrement = null;
                            }
                        }
                        if ($info->default) {
                            $this->sqlStement .= " default " . $info->default;
                        }
                        $this->addIndexOrPrimaryKey($info, $field);
                    });
                    break;

                case "date"     :
                case "datetime" :
                case "timestamp":
                    $value->each(function($info, $field) use ($type){
                        $this->addFieldType($info, $field, $type);
                        $this->addIndexOrPrimaryKey($info, $type);
                    });
                    break;
                case "enum":
                    $value->each(function($info, $field) {
                        $enum = implode(", ", $info["default"]);
                        $this->sqlStement .= " `$field` enum($enum)";
                    });
                    break;
            }
        });

        if ($this->sqlStement !== null) {
            return "create table :table: (". $this->sqlStement . ") engine=" . $this->engine . " default charset=" . $this->character .";";
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
     * @return string
     */
    public function __toString()
    {
        return $this->stringify();
    }
}