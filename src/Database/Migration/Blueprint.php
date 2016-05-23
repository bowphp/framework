<?php
namespace Bow\Database\Migration;

use Bow\Support\Str;
use Bow\Support\Collection;
use Bow\Exception\ModelException;
use Bow\Exception\DatabaseException;

class Blueprint
{
    /**
     * @var ColumnsMaker
     */
    private $columns;

    /**
     * Contructeur.
     *
     * @param ColumnsMaker $columns
     */
    public function __construct(ColumnsMaker $columns)
    {
        $this->columns = $columns;
    }

    /**
     * Génère une chaine requête de type CREATE
     *
     * @return null|string
     */
    public function toCreateTableStatement()
    {
        if ($this->stringify() !== null) {
            return "CREATE TABLE `" . $this->columns->getTableName() . "` (". $this->columns->sqlStement . ") ENGINE=" . $this->columns->getEngine() . " DEFAULT CHARSET=" . $this->columns->getCharacter() . ";";
        }

        return null;
    }

    /**
     * Génère une chaine requête de type CREATE
     *
     * @return null|string
     */
    public function toAlterTableStatement()
    {
        if ($this->stringify() !== null) {

            $sqlArray = explode(", ", $this->columns->sqlStement);
            $sql = '';

            foreach($sqlArray as $key => $value) {
                if ($key > 0) {
                    $sql .= ", ";
                }

                $sql .= "ADD `$value`";
            }

            return "ALTER TABLE " . $this->columns->getTableName() . " " . $sql . ";";
        }

        return null;
    }

    /**
     * stringify
     *
     * @return string
     */
    private function stringify()
    {
        $fields = $this->columns->getDefineFields();

        $fields->each(function (Collection $value, $type) {

            switch ($type) {
                case 'varchar'  :
                case 'char'     :
                case 'text'     :
                case "int"      :
                case "bigint"   :
                case "longint"  :
                    $value->each(function ($info, $field) use ($type) {
                        $this->columns->addFieldType($info, $field, $type);
                        if (in_array($type, ["int", "bigint", "longint"], true)) {
                            if ($this->columns->getAutoincrement() !== false) {
                                if ($this->columns->getAutoincrement()->method == $type && $this->columns->getAutoincrement()->field == $field) {
                                    $this->columns->sqlStement .= " AUTO_INCREMENT";
                                }
                                $this->columns->setAutoincrement(null);
                            }
                        }
                        if ($info["default"]) {
                            $this->columns->sqlStement .= " DEFAULT " . $info["default"];
                        }
                        $this->columns->addIndexOrPrimaryKey($info, $field);
                    });
                    break;

                case "date"     :
                case "datetime" :
                case "timestamp":
                    $value->each(function($info, $field) use ($type){
                        $this->columns->addFieldType($info, $field, $type);
                        $this->columns->addIndexOrPrimaryKey($info, $field);
                    });
                    break;
                case "enum"     :
                    $value->each(function($info, $field) {
                        foreach($info["default"] as $key => $value) {
                            $info["default"][$key] = "'" .  $value . "'";
                        }
                        $null = $this->columns->getNullType($info["null"]);
                        $enum = implode(", ", $info["default"]);
                        $this->columns->sqlStement .= ", `$field` ENUM($enum) $null";
                    });

                    break;
            }
        });

        return $this->columns->sqlStement;
    }
}