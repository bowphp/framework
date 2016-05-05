<?php
namespace Bow\Database\Migration;

use Bow\Exception\DatabaseException;
use Bow\Support\Collection;
use Bow\Exception\ModelException;
use Bow\Support\Str;

class Blueprint
{
    /**
     * @var TableColumnsMaker
     */
    private $columns;

    /**
     * Contructeur.
     *
     * @param TableColumnsMaker $columns
     */
    public function __construct(TableColumnsMaker $columns)
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
            return "CREATE TABLE `" . $this->columns->getTableName() . "` (". $this->columns->sqlStement . ") ENGINE=" . $this->columns->getEngine() . " DEFAULT CHARSET=" . $this->columns->getCharacter() ." COLLATE " . $this->columns->getEngine() .";-";
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
            return "ALTER TABLE ADD " . $this->columns->getTableName() . " ". $this->columns->sqlStement . "; ";
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
                            if ($this->columns->getAutoincrement() !== null) {
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