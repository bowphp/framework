<?php

namespace Bow\Security;

use function count;

class Sanitize
{
    /**
     * Permet de nettoyer les données
     *
     * @param mixed $data
     * @param bool $secure
     *
     * @return mixed
     */
    public static function make($data, $secure = false)
    {
        // récupération de la fonction à la lance.
        $method = $secure === true ? 'secure' : 'data';

        // strict integer regex
        $rNum = '/^[0-9]+(\.[0-9]+)?$/';

        if (is_string($data)) {
            if (! preg_match($rNum, $data, $match)) {
                return static::$method($data);
            }

            if (count($match) == 2) {
                $data = (float) $data;
            } else {
                $data = (int) $data;
            }

            return $data;
        }

        if (is_numeric($data)) {
            return $data;
        }

        if (is_array($data)) {
            foreach($data as $key => $value) {
                $data[$key] = static::make($value, $secure);
            }
            return $data;
        }

        if (is_object($data)) {
            foreach($data as $key => $value) {
                $data->$key = static::make($value, $secure);
            }
            return $data;
        }

        return $data;
    }

    /**
     * Permet de nettoyerune chaine de caractère
     *
     * @param string $data les données a néttoyé
     * @return string
     */
    public static function data($data)
    {
        return stripslashes(stripslashes(trim($data)));
    }

    /**
     * Permet de nettoye rune chaine de caractère
     * ',<tag>,&nbsp;
     *
     * @param string $data les données a sécurisé
     * @return string
     */
    public static function secure($data)
    {
        return htmlspecialchars(addslashes(trim($data)));
    }
}