<?php
namespace Bow\Database\Migration;

use Bow\Support\Collection;

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
        if (($statement = $this->makeSqlStatement()) !== null) {
            return "CREATE TABLE `" . $this->columns->getTableName() . "` ($statement) ENGINE=" . $this->columns->getEngine() . " DEFAULT CHARSET=" . $this->columns->getCharacter() . ";";
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
        if (($statement = $this->makeSqlStatement()) !== null) {

            $sqlArray = explode(", ", $statement);
            $sql = '';

            foreach($sqlArray as $key => $value) {
                if ($key > 0) {
                    $sql .= ", ";
                }

                $sql .= "ADD $value";
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
    private function makeSqlStatement()
    {
        $fields = $this->columns->getDefineFields();
        $statement = (string) $this->columns;

        $fields->each(function (Collection $value, $type) {

            switch ($type) {
                case 'char' :
                case 'tinytext' :
                case 'varchar' :
                case 'text' :
                case 'mediumtext' :
                case 'langtext' :
                case "int" :
                case "tinyint" :
                case "smallint" :
                case "mediumint" :
                case "longint" :
                case "bigint" :
                case "float" :
                case "double precision" :
                case "tinyblob" :
                case "blob" :
                case "mediumblob" :
                case "longblob" :
                    $value->each(function ($info, $field) use ($type, &$statement) {
                        $this->columns->addFieldType($info, $field, $type);
                        if (in_array($type, ["int", "longint", "bigint", "mediumint", "smallint", "tinyint"], true)) {
                            if ($this->columns->getAutoincrement() !== false) {
                                if ($this->columns->getAutoincrement()->method == $type && $this->columns->getAutoincrement()->field == $field) {
                                    $statement .= " AUTO_INCREMENT";
                                }
                                $this->columns->setAutoincrement(false);
                            }
                        }
                        if ($info["default"]) {
                            $statement .= " DEFAULT " . $info["default"];
                        }
                        $this->columns->addIndexOrPrimaryKey($info, $field);
                    });
                    break;

                case "date" :
                case "datetime" :
                case "timestamp" :
                case "time" :
                case "year" :
                    $value->each(function($info, $field) use ($type, &$statement){
                        $this->columns->addFieldType($info, $field, $type);
                        $this->columns->addIndexOrPrimaryKey($info, $field);
                    });
                    break;
                case "enum" :
                    $value->each(function($info, $field) use (&$statement) {
                        foreach($info["default"] as $key => $value) {
                            $info["default"][$key] = "'" .  $value . "'";
                        }
                        $null = $this->columns->getNullType($info["null"]);
                        $enum = implode(", ", $info["default"]);
                        $statement .= ", `$field` ENUM($enum) $null";
                    });

                    break;
            }
        });

        return $statement;
    }
}