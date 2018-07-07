<?php
namespace Bow\Support;

/**
 * Class Str
 *
 * @author  Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Support
 */
class Str
{
    /**
     * upper case
     *
     * @param  string $str
     * @return array|string
     */
    public static function upper($str)
    {
        if (is_string($str)) {
            $str = mb_strtoupper($str, 'UTF-8');
        }

        return $str;
    }

    /**
     * lower case
     *
     * @param  string $str
     * @return array|string
     */
    public static function lower($str)
    {
        if (is_string($str)) {
            $str = mb_strtolower($str, 'UTF-8');
        }

        return $str;
    }

    /**
     * camel
     *
     * @param  string $str
     * @return string
     */
    public static function camel($str)
    {
        $parts = preg_split('/(_|-|\s)+/', $str);

        $camel = "";

        foreach ($parts as $key => $value) {
            if ($key == 0) {
                $camel .= $value;

                continue;
            }

            $camel .= ucfirst($value);
        }

        return $camel;
    }

    /**
     * Snake case
     *
     * @param  $value
     * @param  string $delimiter
     * @return mixed
     */
    public static function snake($value, $delimiter = '_')
    {
        $value = preg_replace('/\s+/u', $delimiter, $value);

        $value = static::lower(preg_replace_callback('/([A-Z])/u', function ($math) use ($delimiter) {
            return $delimiter.static::lower($math[1]);
        }, $value));

        return preg_replace('/'.$delimiter.'{2,}/', $delimiter, $value);
    }

    /**
     * slice
     *
     * @param  string $str
     * @param  $start
     * @param  null   $end
     * @return string
     */
    public static function slice($str, $start, $end = null)
    {
        $sliceStr = '';

        if (is_string($str)) {
            if ($end === null) {
                $end = static::len($str);
            }

            if ($start < $end) {
                $sliceStr = mb_substr($str, $start, $end, 'UTF-8');
            }
        }

        return $sliceStr;
    }

    /**
     * split
     *
     * @param string $pattern
     * @param string $str
     * @param null   $limit
     *
     * @return array
     */
    public static function split($pattern, $str, $limit = null)
    {
        return mb_split($pattern, $str, $limit);
    }

    /**
     * Get the string position
     *
     * @param string $search
     * @param string $string
     * @param int    $offset
     *
     * @return int
     */
    public static function pos($search, $string, $offset = 0)
    {
        return mb_strpos($string, $search, $offset, 'UTF-8');
    }

    /**
     * Contains
     *
     * @param string $search
     * @param string $str
     *
     * @return bool
     */
    public static function contains($search, $str)
    {
        if ($search === $str) {
            return true;
        }

        return static::pos($search, $str);
    }

    /**
     * replace
     *
     * @param $pattern
     * @param $replaceBy
     * @param $str
     *
     * @return string
     */
    public static function replace($pattern, $replaceBy, $str)
    {
        return str_replace($pattern, $replaceBy, $str);
    }

    /**
     * capitalize
     *
     * @param $str
     *
     * @return string
     */
    public static function capitalize($str)
    {
        return ucwords($str);
    }

    /**
     * len, retourne la taille d'une chaine.
     *
     * @param $str
     *
     * @return int
     */
    public static function len($str)
    {
        return mb_strlen($str, 'UTF-8');
    }

    /**
     * wordify
     *
     * @param $str
     * @param $sep
     *
     * @return array
     */
    public static function wordify($str, $sep = ' ')
    {
        return static::split($sep, $str, static::count($sep, $str));
    }

    /**
     * repeat, réperte la chaine de caractère dans une nombre déterminé
     *
     * @param $str
     * @param $number
     *
     * @return string
     */
    public static function repeat($str, $number)
    {
        return str_repeat($str, $number);
    }

    /**
     * randomize
     *
     * @param int $size
     *
     * @return string
     */
    public static function randomize($size = 16)
    {
        return static::slice(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $size);
    }

    /**
     * slugify créateur de slug en utilisant un chaine simple.
     * eg: 'je suis un chaine de caractere' => 'je-suis-un-chaine-de-caractere'
     *
     * @param string $str
     *
     * @return string
     */
    public static function slugify($str)
    {
        $temp = preg_replace('/[^a-z0-9]/', '-', strtolower(trim(strip_tags($str))));

        return preg_replace('/-{2,}/', '-', $temp);
    }

    /**
     * unslugify créateur de slug en utilisant un chaine simple.
     *
     * @param string $str
     *
     * @return string
     */
    public static function unSlugify($str)
    {
        return preg_replace('/[^a-z0-9]/', ' ', strtolower(trim(strip_tags($str))));
    }

    /**
     * Vérifier si le mail est un mail valide.
     *
     * eg: dakiafranck@gmail.com => true
     *
     * @param string $email
     * @return bool
     */
    public static function isMail($email)
    {
        $parts = explode('@', $email);

        if (!is_string($email) || count($parts) != 2) {
            return false;
        }

        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Vérifie si la chaine est un domaine
     *
     * eg: http://exemple.com => true
     * eg: http:/exemple.com => false
     *
     * @param string $domain
     * @return bool
     *
     * @throws \ErrorException
     */
    public static function isDomain($domain)
    {
        if (!is_string($domain)) {
            throw new \ErrorException('Accept string ' . gettype($domain) . ' given');
        }

        return  (bool) preg_match('/^((https?|ftps?|ssl|url|git):\/\/)?[a-zA-Z0-9-_.]+\.[a-z]{2,6}$/', $domain);
    }

    /**
     * Vérifie si la chaine est en alphanumeric
     *
     * @param string $str*
     * @return bool
     *
     * @throws \ErrorException
     */
    public static function isAlphaNum($str)
    {
        if (!is_string($str)) {
            throw new \ErrorException('Accept string ' . gettype($str) . ' given');
        }

        return (bool) preg_match('/^[a-zA-Z0-9]+$/', $str);
    }

    /**
     * Vérifie si la chaine est en numeric
     *
     * @param string $str
     * @return bool
     *
     * @throws \ErrorException
     */
    public static function isNumeric($str)
    {
        if (!is_string($str)) {
            throw new \ErrorException('Accept string ' . gettype($str) . ' given');
        }

        return  (bool) preg_match('/^[0-9]+(\.[0-9]+)?$/', $str);
    }

    /**
     * Vérifie si la chaine est en alpha
     *
     * @param string $str
     * @return bool
     *
     * @throws \ErrorException
     */
    public static function isAlpha($str)
    {
        if (!is_string($str)) {
            throw new \ErrorException('Accept string ' . gettype($str) . ' given');
        }

        return (bool) preg_match('/^[a-zA-Z]+$/', $str);
    }

    /**
     * Vérifie si la chaine est en format slug
     *
     * @param string $str
     * @throws \ErrorException
     * @return bool
     */
    public static function isSlug($str)
    {
        if (!is_string($str)) {
            throw new \ErrorException('Accept string ' . gettype($str) . ' given');
        }

        return  (bool) preg_match('/^[a-z0-9-]+[a-z0-9]+$/', $str);
    }

    /**
     * Vérifie si la chaine est en majiscule
     *
     * @param  string $str
     * @return bool
     */
    public static function isUpper($str)
    {
        return static::upper($str) === $str;
    }

    /**
     * Vérifie si la chaine est en miniscule
     *
     * @param  string $str
     * @return bool
     */
    public static function isLower($str)
    {
        return static::lower($str) === $str;
    }

    /**
     * Retourne le nombre caractère dans une chaine.
     *
     * @param string $pattern
     * @param string $str
     *
     * @return int
     */
    public static function count($pattern, $str)
    {
        return count(explode($pattern, $str)) - 1;
    }

    /**
     * Retourne un nombre détermine de mots dans une chaine de caractère.
     *
     * @param string $words
     * @param int    $len
     *
     * @return string
     */
    public static function getWords($words, $len)
    {
        $wordParts = explode(' ', $words);

        $sentence = '';

        for ($i = 0; $i < $len; $i++) {
            $sentence .= ' ' . $wordParts[$i];
        }

        return trim($sentence);
    }

    /**
     * Retourne une chaine de caractère dont les mots sont mélangés.
     *
     * @param string $words
     *
     * @return string
     */
    public static function shuffleWords($words)
    {
        $wordParts = explode(' ', trim($words));

        $wordPartsLen = count($wordParts);

        $rand = [];

        do {
            $r = rand(0, $wordPartsLen - 1);

            if (!in_array($r, $rand)) {
                $rand[] = $r;
            }
        } while (count($rand) != $wordPartsLen);

        $sentence = '';

        foreach ($rand as $word) {
            $sentence .= $wordParts[$word] . ' ';
        }

        return trim($sentence);
    }

    /**
     * Permet de forcer l'encodage en utf-8
     */
    public static function forceInUTF8()
    {
        mb_internal_encoding('UTF-8');

        mb_http_output('UTF-8');
    }

    /**
     * __call
     *
     * @param  string $method
     * @param  array  $arguments
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        return call_user_func_array([static::class, $method], $arguments);
    }
}
