<?php
namespace Bow\Database\Migration;

use Bow\Support\Collection;

class Statement
{
    /**
     * La requète SQL
     *
     * @var string
     */
    private $sql;

    /**
     * @var Fields
     */
    private $columns;

    /**
     * Contructeur.
     *
     * @param Fields $columns
     */
    public function __construct(Fields $columns)
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

            foreach ($sqlArray as $key => $value) {
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
        /**
         * Les informations entrées par l'utilisateur.
         */
        $fields = $this->columns->getDefineFields();

        $fields->each(function (Collection $value, $type) {

            switch ($type) {
                case 'char' :
                case 'tinytext' :
                case 'varchar' :
                case 'text' :
                case 'mediumtext' :
                case 'longtext' :
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
                    $value->each(function ($info, $field) use ($type) {
                        $this->addFieldType($info, $field, $type);
                        if (in_array($type, ["int", "longint", "bigint", "mediumint", "smallint", "tinyint"], true)) {
                            if ($this->columns->getAutoincrement() !== false) {
                                if ($this->columns->getAutoincrement()->method == $type && $this->columns->getAutoincrement()->field == $field) {
                                    $this->sql .= " AUTO_INCREMENT";
                                }
                                $this->columns->setAutoincrement(false);
                            }
                        }
                        if ($info["default"]) {
                            $this->sql .= " DEFAULT " . $info["default"];
                        }
                        $this->addIndexOrPrimaryKey($info, $field);
                    });
                    break;

                case "date" :
                case "datetime" :
                case "timestamp" :
                case "time" :
                case "year" :
                    $value->each(function ($info, $field) use ($type) {
                        $this->addFieldType($info, $field, $type);
                        $this->addIndexOrPrimaryKey($info, $field);
                    });
                    break;
                case "enum" :
                    $value->each(function ($info, $field) {
                        foreach ($info["value"] as $key => $value) {
                            $info["value"][$key] = "'" . $value . "'";
                        }
                        $null = $this->getNullType($info["null"]);
                        $enum = implode(", ", $info["value"]);
                        $this->sql .= "`$field` ENUM($enum) $null";
                        if ($info["default"] !== null) {
                            $this->sql .= " DEFAULT '" . $info["default"] . "'";
                        }
                    });

                    break;
            }
        });

        return $this->sql;
    }

    /**
     * getNullType retourne les valeurs "null" ou "not null"
     *
     * @param bool $null
     * @return string
     */
    private function getNullType($null)
    {
        if ($this->sql != null) {
            $this->sql .= ", ";
        }

        $nullType = "NOT NULL";

        if ($null === true) {
            $nullType = "NULL";
        }

        return $nullType;
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
        $null = $this->getNullType($info["null"]);
        $type = strtoupper($type);

        if (isset($info['size'])) {
            $info['size'] = "(". $info['size'] .")";
        } else {
            $info['size'] = "";
        }

        $this->sql .= "`$field` $type{$info['size']} $null";
    }

    /**
     * Ajout les indexes et la clé primaire.
     *
     * @param \StdClass $info
     * @param string    $field
     */
    private function addIndexOrPrimaryKey($info, $field)
    {
        if ($info["primary"]) {
            $this->sql .= " PRIMARY KEY";
            $info["primary"] = false;
        } else {
            if ($info["unique"]) {
                $this->sql .= " UNIQUE";
                $info["unique"] = false;
            } else {
                if (isset($info["indexes"])) {
                    $this->sql .= ", INDEXE `" . $this->columns->getTableName() . "_indexe_" . $field . "` (`" . $field . "`)";
                    $info["indexes"] = false;
                }
            }
        }
    }
}