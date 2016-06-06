<?php
namespace Bow\Database\Migration;

use Bow\Support\Str;
use Bow\Support\Collection;
use Bow\Exception\ModelException;

class ColumnsMaker
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
    public $sqlStement = null;

    /**
     * @var string
     */
    private $engine = "MyISAM";

    /**
     * @var string
     */
    private $collate = "utf8_unicode_ci";

    /**
     * @var string
     */
    private $character = "UTF8";

    /**
     * define the auto increment field
     * @var \StdClass
     */
    private $autoincrement = false;

    /**
     * @var bool
     */
    private $displaySql = false;

    /**
     * @var array
     */
    private $dataBind = [];

    /**
     * Constructor
     *
     * @param string $table nom de la table
     * @param bool $displaySql
     */
    public function __construct($table, $displaySql = false)
    {
        $this->fields  = new Collection;
        $this->table = $table;
        $this->displaySql = $displaySql;
        return $this;
    }

    /**
     * charset, set the model default character name
     *
     * @param $character
     */
    public function charset($character)
    {
        $this->character = $character;
    }

    /**
     * setEngine, set the model engine name
     * @param $collate
     */
    public function collate($collate)
    {
        $this->collate = $collate;
    }

    /**
     * setEngine, set the model engine name
     * @param $engine
     */
    public function engine($engine)
    {
        $this->engine = $engine;
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
        return $this->loadWhole("int", $field, $size, $null, $default);
    }

    /**
     * tinyint
     *
     * @param string $field
     * @param bool $null
     * @param bool $size
     * @param null|string $default
     *
     * @return $this
     */
    public function tinyint($field, $size = null, $null = false, $default = null)
    {
        return $this->loadWhole("tinyint", $field, $size, $null, $default);
    }

    /**
     * smallint
     *
     * @param string $field
     * @param bool $size
     * @param bool $null
     * @param null|string $default
     *
     * @return $this
     * @throws \ErrorException
     */
    public function smallint($field, $size = null, $null = false, $default = null)
    {
        return $this->loadWhole("smallint", $field, $size, $null, $default);
    }

    /**
     * mediumint
     *
     * @param string $field
     * @param bool $size
     * @param bool $null
     * @param null|string $default
     *
     * @return $this
     * @throws \ErrorException
     */
    public function mediumint($field, $size = null, $null = false, $default = null)
    {
        return $this->loadWhole("mediumint", $field, $size, $null, $default);
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
     * bigint
     *
     * @param string $field
     * @param int $size
     * @param int $left
     * @param bool $null
     * @param null|string $default
     *
     * @return $this
     */
    public function double($field, $size = 20, $left = 0, $null = false, $default = null)
    {
        if ($left > 0) {
            $size = "$size, $left";
        }
        return $this->loadWhole("double precision", $field, $size, $null, $default);
    }

    /**
     * bigint
     *
     * @param string $field
     * @param int $size
     * @param int $left
     * @param bool $null
     * @param null|string $default
     *
     * @return $this
     */
    public function float($field, $size = 20, $left = 0, $null = false, $default = null)
    {
        if ($left > 0) {
            $size = "$size, $left";
        }
        return $this->loadWhole("float", $field, $size, $null, $default);
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
     * year
     *
     * @param string $field
     * @param bool $null
     *
     * @return $this
     */
    public function year($field, $null = false)
    {
        $this->addField("year", $field, [
            "null" => $null
        ]);

        return $this;
    }

    /**
     * time
     *
     * @param string $field
     * @param bool $null
     *
     * @return $this
     */
    public function time($field, $null = false)
    {
        $this->addField("time", $field, [
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
    public function dateTime($field, $null = false)
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
     * @param bool $null
     * @return Schema
     */
    public function enumerate($field, array $enums, $null = false)
    {
        $this->addField("enum", $field, [
            "default" => $enums,
            "null" => $null
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
        if ($this->autoincrement === false) {
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
        if ($this->lastField === null) {
            throw new ModelException("Cannot assign {$indexType}. Because field are not defined.", E_ERROR);
        }

        $last = $this->lastField;
        $this->fields->get($last->method)->update($last->field, [$indexType => true]);

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

        if ($this->fields->get($method)->has($field)) {
            return $this;
        }

        if (in_array($method, ["int", "longint", "bigint"])) {
            if ($this->getAutoincrement()) {
                $value = "NULL";
            } else {
                $value = Filler::number();
            }
        } else if (in_array($method, ["date", "datetime"])) {
            $value = Filler::date();
        } else if (in_array($method, ["double", "float"])) {
            $value = Filler::float();
        } else if ($method == "timestamp") {
            $value = "CURRENT_TIMESTAMP";
        } else {
            $value = Str::slice(Filler::string(), 0, $data["size"]);
        }

        if (!is_array($this->dataBind)) {
            $this->dataBind = [];
        }

        $this->dataBind[$field] = $value;

        // default index are at false
        $data["primary"] = false;
        $data["unique"]  = false;
        $data["indexe"]  = false;

        $this->fields->get($method)->add($field, $data);

        $this->lastField = (object) [
            "method" => $method,
            "field"  => $field
        ];

        return $this;
    }

    /**
     * loadWhole
     *
     * @param string      $method
     * @param string      $field
     * @param int         $size
     * @param bool        $null
     * @param null|string $default
     *
     * @return $this
     */
    private function loadWhole($method, $field, $size = 20, $null = false, $default = null)
    {
        if (is_bool($size)) {
            $default = $null === false ? null : $null;
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
     * @param string    $field
     */
    public function addIndexOrPrimaryKey($info, $field)
    {
        if ($info["primary"]) {
            $this->sqlStement .= " PRIMARY KEY";
            $info["primary"] = false;
        } else {
            if ($info["unique"]) {
                $this->sqlStement .= " UNIQUE";
                $info["unique"] = false;
            } else {
                if (isset($info["indexes"])) {
                    $this->sqlStement .= ", INDEXE `" . $this->table . "_indexe_" . $field . "` (`" . $field . "`)";
                    $info["indexes"] = false;
                }
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
    public function addFieldType($info, $field, $type)
    {
        $null = $this->getNullType($info["null"]);
        $type = strtoupper($type);

        if (isset($info['size'])) {
            $info['size'] = "(". $info['size'] .")";
        } else {
            $info['size'] = "";
        }

        $this->sqlStement .= "`$field` $type{$info['size']} $null";
    }

    /**
     * getNullType retourne les valeurs "null" ou "not null"
     *
     * @param bool $null
     * @return string
     */
    public function getNullType($null)
    {
        if ($this->sqlStement != null) {
            $this->sqlStement .= ", ";
        }

        $nullType = "NOT NULL";

        if ($null) {
            $nullType = "NULL";
        }

        return $nullType;
    }

    /**
     * @return Collection
     */
    public function getDefineFields()
    {
        return $this->fields;
    }

    /**
     * @return bool
     */
    public function getDisplaySql()
    {
        return $this->displaySql;
    }

    /**
     * @return bool|string
     */
    public function getTableName()
    {
        return $this->table;
    }

    /**
     * @return string
     */
    public function getCharacter()
    {
        return $this->character;
    }

    /**
     * @return string
     */
    public function getEngine()
    {
        return $this->engine;
    }

    /**
     * @return \stdClass
     */
    public function getAutoincrement()
    {
        return $this->autoincrement;
    }

    /**
     * @param $value
     */
    public function setAutoincrement($value)
    {
        $this->autoincrement = $value;
    }

    /**
     * @return array
     */
    public function getBindData()
    {
        return $this->dataBind;
    }
}