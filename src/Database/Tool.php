<?php

namespace Bow\Database;

use PDO;
use PDOStatement;

class Tool
{
    /**
     * Éxécute PDOStatement::bindValue sur une instance de PDOStatement passé en paramètre
     *
     * @param PDOStatement $pdoStatement
     * @param array $data
     *
     * @return PDOStatement
     */
    public function bind(PDOStatement $pdoStatement, array $data = [])
    {
        foreach ($data as $key => $value) {
            if (is_null($value) || strtolower($value) === 'null') {
                $pdoStatement->bindValue(':' . $key, $value, PDO::PARAM_NULL);
                unset($data[$key]);
            }
        }

        foreach ($data as $key => $value) {
            $param = PDO::PARAM_INT;

            if (preg_match('/[a-zA-Z_-]+|éàèëïùöôîüµ$£!?\.\+,;:/', $value)) {
                /**
                 * SÉCURIATION DES DONNÉS
                 * - Injection SQL
                 * - XSS
                 */
                $param = PDO::PARAM_STR;
            } else {
                /**
                 * On force la valeur en entier ou en réél.
                 */
                if (is_int($value)) {
                    $value = (int) $value;
                } elseif (is_float($value)) {
                    $value = (float) $value;
                } else {
                    $value = (double) $value;
                }
            }

            if (is_string($key)) {
                $pdoStatement->bindValue(':' . $key, $value, $param);
            } else {
                $pdoStatement->bindValue($key + 1, $value, $param);
            }
        }

        return $pdoStatement;
    }
}