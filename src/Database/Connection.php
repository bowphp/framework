<?php

namespace Bow\Database;

use PDO;
use PDOException;
use Bow\Support\Util;

class Connection
{
    /**
     * Variable d'instance la connection.
     *
     * @var null
     */
    private static $db = null;

    /**
     * retourne l'instance de pdo
     *
     * @return PDO
     */
    public static function pdo()
    {

        return static::$db;
    }
}