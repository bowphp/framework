<?php

namespace Snoop\Database;

use PDO;
use PDOStatement;
use Snoop\Support\Security;

abstract class DatabaseTools
{

    /**
     * Éxécute PDOStatement::bindValue sur une instance de PDOStatement passé en paramètre
     *
     * @param PDOStatement $pdoStatement
     * @param $data
     * 
     * @return PDOStatement
     */
    protected static function bind(PDOStatement $pdoStatement, array $data = [])
    {
        foreach ($data as $key => $value) {

            if (is_null($value) || $value === "NULL") {
                continue;
            }

            $param = PDO::PARAM_INT;

            if (preg_match("/[a-zA-Z_-]+/", $value)) {

                /**
                 * SÉCURIATION DES DONNÉS
                 * - Injection SQL
                 * - XSS
                 */
                $param = PDO::PARAM_STR;
                $value = Security::sanitaze($value, true);

            } else {
                /**
                 * On force la valeur en entier ou en réél.
                 */
                if(is_int($value)) {
                    $value = (int) $value;
                } else if (is_float($value)) {
                    $value = (float) $value;
                }
            }
            /**
             * Exécution de bindValue
             */
            if (is_string($key)) {
                $pdoStatement->bindValue(":$key", $value, $param);
            } else {
                $pdoStatement->bindValue($key, $value, $param);
            }
        }
    }

    /**
     * rangeField, fonction permettant de sécuriser les données.
     *
     * @param array $data, les données à sécuriser
     * 
     * @return string $field
     */
    protected static function rangeField($data)
    {
        $field = "";
        $i = 0;
        foreach ($data as $key => $value) {
            /**
             * Construction d'une chaine de format:
             * key1 = value1, key2 = value2[, keyN = valueN]
             * Utile pour binder une réquette INSERT en mode preparer:
             */
            $field .= ($i > 0 ? ", " : "") . $key . " = " . $value;
            $i++;
        }
        /**
         * Retourne une chaine de caractère.
         */
        return $field;
    }
    
    /**
     * Formateur de donnée. key => :value
     *
     * @param array $data
     * 
     * @return array $resultat
     */
    protected function add2points(array $data)
    {
        $resultat = [];

        foreach ($data as $key => $value) {
            $resultat[$value] = ":$value";
        }

        return $resultat;
    }
}