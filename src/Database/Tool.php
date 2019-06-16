<?php

namespace Bow\Database;

use PDO;
use PDOStatement;

class Tool
{
    /**
     * Executes PDOStatement::bindValue on an instance of
     * PDOStatement passed as parameter
     *
     * @param PDOStatement $pdo_statement
     * @param array $data
     *
     * @return PDOStatement
     */
    public function bind(PDOStatement $pdo_statement, array $data = [])
    {
        foreach ($data as $key => $value) {
            if (is_null($value) || strtolower($value) === 'null') {
                $pdo_statement->bindValue(
                    ':' . $key,
                    $value,
                    PDO::PARAM_NULL
                );
                
                unset($data[$key]);
            }
        }

        foreach ($data as $key => $value) {
            $param = PDO::PARAM_INT;

            /**
             * We force the value in whole or in real.
             *
             * SECURITY OF DATA
             * - Injection SQL
             * - XSS
             */
            if (is_int($value)) {
                $value = (int) $value;
            } elseif (is_float($value)) {
                $value = (float) $value;
            } elseif (is_double($value)) {
                $value = (double) $value;
            } else {
                $param = PDO::PARAM_STR;
            }

            if (is_string($key)) {
                $pdo_statement->bindValue(':' . $key, $value, $param);
            } else {
                $pdo_statement->bindValue($key + 1, $value, $param);
            }
        }

        return $pdo_statement;
    }
}
