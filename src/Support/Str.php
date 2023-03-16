<?php

namespace Bow\Support;

use ErrorException;
use ForceUTF8\Encoding;

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
     * @param  string $str
     * @param  string $delimiter
     * @return mixed
     */
    public static function snake($str, $delimiter = '_')
    {
        $str = preg_replace('/\s+/u', $delimiter, $str);

        $str = static::lower(preg_replace_callback('/([A-Z])/u', function ($math) use ($delimiter) {
            return $delimiter . static::lower($math[1]);
        }, $str));

        return trim(preg_replace('/' . $delimiter . '{2,}/', $delimiter, $str), $delimiter);
    }

    /**
     * Get str plurial
     *
     * @param string $str
     * @return string
     */
    public static function plurial($str)
    {
        if (preg_match('/y$/', $str)) {
            $str = static::slice($str, 0, static::len($str) - 1);

            return $str . 'ies';
        }

        preg_match('/s$/', $str) ?: $str = $str . 's';

        return $str;
    }

    /**
     * slice
     *
     * @param  string $str
     * @param  string $start
     * @param  string|null $end
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
     * @param int|null $limit
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
     * @param int $offset
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
     * @param string $pattern
     * @param string $replaceBy
     * @param string $str
     * @return string
     */
    public static function replace($pattern, $replaceBy, $str)
    {
        return str_replace($pattern, $replaceBy, $str);
    }

    /**
     * capitalize
     *
     * @param string $str
     * @return string
     */
    public static function capitalize($str)
    {
        return ucwords($str);
    }

    /**
     * Len
     *
     * @param string $str
     * @return int
     */
    public static function len($str)
    {
        return mb_strlen($str, 'UTF-8');
    }

    /**
     * Wordify
     *
     * @param string $str
     * @param string $sep
     * @return array
     */
    public static function wordify($str, $sep = ' ')
    {
        return static::split($sep, $str, static::count($sep, $str));
    }

    /**
     * Lists the string of characters in a specified number
     *
     * @param string $str
     * @param string $number
     * @return string
     */
    public static function repeat($str, $number)
    {
        return str_repeat($str, $number);
    }

    /**
     * Randomize
     *
     * @param int $size
     * @return string
     */
    public static function randomize($size = 16)
    {
        return static::slice(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $size);
    }

    /**
     * slugify slug creator using a simple chain.
     * eg: 'I am a string of character' => 'i-am-a-chain-of-character'
     *
     * @param string $str
     * @param string $delimiter
     * @return string
     */
    public static function slugify($str, $delimiter = '-')
    {
        $temp = preg_replace(
            '/[^a-z0-9]/',
            $delimiter,
            strtolower(trim(strip_tags($str)))
        );

        return preg_replace('/-{2,}/', $delimiter, $temp);
    }

    /**
     * unslugify, Lets you undo a slug
     *
     * @param string $str
     * @return string
     */
    public static function unSlugify($str)
    {
        return preg_replace('/[^a-z0-9]/', ' ', strtolower(trim(strip_tags($str))));
    }

    /**
     * Check if the email is a valid email.
     *
     * eg: example@email.com => true
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
     * Check if the string is a domain
     *
     * eg: http://exemple.com => true
     * eg: http:/exemple.com => false
     *
     * @param string $domain
     * @return bool
     * @throws ErrorException
     */
    public static function isDomain($domain)
    {
        if (!is_string($domain)) {
            throw new ErrorException('Accept string ' . gettype($domain) . ' given');
        }

        return  (bool) preg_match(
            '/^((https?|ftps?|ssl|url|git):\/\/)?[a-zA-Z0-9-_.]+\.[a-z]{2,6}$/',
            $domain
        );
    }

    /**
     * Check if the string is in alphanumeric
     *
     * @param string $str
     * @return bool
     * @throws ErrorException
     */
    public static function isAlphaNum($str)
    {
        if (!is_string($str)) {
            throw new ErrorException('Accept string ' . gettype($str) . ' given');
        }

        return (bool) preg_match('/^[a-zA-Z0-9]+$/', $str);
    }

    /**
     * Check if the string is in numeric
     *
     * @param string $str
     * @return bool
     * @throws ErrorException
     */
    public static function isNumeric($str)
    {
        if (!is_string($str)) {
            throw new ErrorException('Accept string ' . gettype($str) . ' given');
        }

        return  (bool) preg_match('/^[0-9]+(\.[0-9]+)?$/', $str);
    }

    /**
     * Check if the string is in alpha
     *
     * @param string $str
     * @return bool
     * @throws ErrorException
     */
    public static function isAlpha($str)
    {
        if (!is_string($str)) {
            throw new ErrorException('Accept string ' . gettype($str) . ' given');
        }

        return (bool) preg_match('/^[a-zA-Z]+$/', $str);
    }

    /**
     * Check if the string is in slug format
     *
     * @param string $str
     * @return bool
     * @throws ErrorException
     */
    public static function isSlug($str)
    {
        if (!is_string($str)) {
            throw new ErrorException('Accept string ' . gettype($str) . ' given');
        }

        return  (bool) preg_match('/^[a-z0-9-]+[a-z0-9]+$/', $str);
    }

    /**
     * Check if the string is in uppercase
     *
     * @param  string $str
     * @return bool
     */
    public static function isUpper($str)
    {
        return static::upper($str) === $str;
    }

    /**
     * Check if the string is lowercase
     *
     * @param  string $str
     * @return bool
     */
    public static function isLower($str)
    {
        return static::lower($str) === $str;
    }

    /**
     * Returns the number of characters in a string.
     *
     * @param string $pattern
     * @param string $str
     * @return int
     */
    public static function count($pattern, $str)
    {
        return count(explode($pattern, $str)) - 1;
    }

    /**
     * Returns a determined number of words in a string.
     *
     * @param string $words
     * @param int $len
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
     * Returns a string of words whose words are mixed.
     *
     * @param string $words
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
     * Enables to force the encoding in utf-8
     *
     * @return void
     */
    public static function forceInUTF8()
    {
        mb_internal_encoding('UTF-8');

        mb_http_output('UTF-8');
    }

    /**
     * Enables to force the encoding in utf-8
     *
     * @param string $garbled_utf8_string
     * @return string
     */
    public static function fixUTF8($garbled_utf8_string)
    {
        $utf8_string = Encoding::fixUTF8($garbled_utf8_string);

        return $utf8_string;
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
