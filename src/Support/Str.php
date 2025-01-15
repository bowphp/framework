<?php

declare(strict_types=1);

namespace Bow\Support;

use ErrorException;
use ForceUTF8\Encoding;
use Ramsey\Uuid\Uuid;

class Str
{
    /**
     * upper case
     *
     * @param string $str
     * @return string
     */
    public static function upper(string $str): string
    {
        return mb_strtoupper($str, 'UTF-8');
    }

    /**
     * lower case
     *
     * @param string $str
     * @return string
     */
    public static function lower(string $str): string
    {
        return mb_strtolower($str, 'UTF-8');
    }

    /**
     * camel
     *
     * @param  string $str
     * @return string
     */
    public static function camel(string $str): string
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
     * @return string
     */
    public static function snake(string $str, string $delimiter = '_'): string
    {
        $str = preg_replace('/\s+/u', $delimiter, $str);

        $str = static::lower(preg_replace_callback('/([A-Z])/u', function ($math) use ($delimiter) {
            return $delimiter . static::lower($math[1]);
        }, $str));

        return trim(preg_replace('/' . $delimiter . '{2,}/', $delimiter, $str), $delimiter);
    }

    /**
     * Get str plural
     *
     * @param string $str
     * @return string
     */
    public static function plural(string $str): string
    {
        if (str_ends_with($str, 'y')) {
            $str = static::slice($str, 0, static::len($str) - 1);

            return $str . 'ies';
        }

        str_ends_with($str, 's') ?: $str = $str . 's';

        return $str;
    }

    /**
     * slice
     *
     * @param string $str
     * @param int $start
     * @param int|null $length
     * @return string
     */
    public static function slice(string $str, int $start, ?int $length = null): string
    {
        $slice_str = '';

        if ($length === null) {
            $length = static::len($str);
        }

        if ($start < $length) {
            $slice_str = mb_substr($str, $start, $length, 'UTF-8');
        }

        return $slice_str;
    }

    /**
     * split
     *
     * @param string $pattern
     * @param string $str
     * @param int|null $limit
     * @return array
     */
    public static function split(string $pattern, string $str, ?int $limit = null): array
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
    public static function pos(string $search, string $string, int $offset = 0): int
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
    public static function contains(string $search, string $str): bool
    {
        if ($search === $str) {
            return true;
        }

        return (bool) static::pos($search, $str);
    }

    /**
     * replace
     *
     * @param string $pattern
     * @param string $replaceBy
     * @param string $str
     * @return string
     */
    public static function replace(string $pattern, string $replaceBy, string $str): string
    {
        return str_replace($pattern, $replaceBy, $str);
    }

    /**
     * capitalize
     *
     * @param string $str
     * @return string
     */
    public static function capitalize(string $str): string
    {
        return ucwords($str);
    }

    /**
     * Len
     *
     * @param string $str
     * @return int
     */
    public static function len(string $str): int
    {
        return mb_strlen($str, 'UTF-8');
    }

    /**
     * Wordily
     *
     * @param string $str
     * @param string $sep
     * @return array
     */
    public static function wordily(string $str, string $sep = ' '): array
    {
        return static::split($sep, $str, static::count($sep, $str));
    }

    /**
     * Lists the string of characters in a specified number
     *
     * @param string $str
     * @param int $number
     * @return string
     */
    public static function repeat(string $str, int $number): string
    {
        return str_repeat($str, $number);
    }

    /**
     * Randomize
     *
     * @param int $size
     * @return string
     */
    public static function random(int $size = 16): string
    {
        return static::slice(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $size);
    }

    /**
     * Get random uuid
     *
     * @return string
     */
    public static function uuid(): string
    {
        return Uuid::uuid4()->toString();
    }

    /**
     * slugify slug creator using a simple chain.
     * eg: 'I am a string of character' => 'i-am-a-chain-of-character'
     *
     * @param string $str
     * @param string $delimiter
     * @return string
     */
    public static function slugify(string $str, string $delimiter = '-'): string
    {
        $temp = preg_replace(
            '/[^a-z0-9]/',
            $delimiter,
            strtolower(trim(strip_tags($str)))
        );

        return preg_replace('/-{2,}/', $delimiter, $temp);
    }

    /**
     * Alias of slugify
     *
     * @param string $str
     * @param string $delimiter
     * @return string
     */
    public static function slug(string $str, string $delimiter = '-'): string
    {
        return static::slugify($str, $delimiter);
    }

    /**
     * un-slugify, Lets you undo a slug
     *
     * @param string $str
     * @return string
     */
    public static function unSlugify(string $str): string
    {
        return preg_replace('/[^a-z0-9]/', ' ', strtolower(trim(strip_tags($str))));
    }

    /**
     * Alias of un-slugify
     *
     * @param string $str
     * @return string
     */
    public static function unSlug(string $str): string
    {
        return static::unSlugify($str);
    }

    /**
     * Check if the email is a valid email.
     *
     * eg: example@email.com => true
     *
     * @param string $email
     * @return bool
     */
    public static function isMail(string $email): bool
    {
        $parts = explode('@', $email);

        if (count($parts) != 2) {
            return false;
        }

        return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Check if the string is a domain
     *
     * eg: http://example.com => true
     * eg: http:/example.com => false
     *
     * @param string $domain
     * @return bool
     */
    public static function isDomain(string $domain): bool
    {
        return (bool) preg_match(
            '/^((https?|ftps?|ssl|url|git):\/\/)?[a-zA-Z0-9-_.]+\.[a-z]{2,6}$/',
            $domain
        );
    }

    /**
     * Check if the string is in alphanumeric
     *
     * @param string $str
     * @return bool
     */
    public static function isAlphaNum(string $str): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9]+$/', $str);
    }

    /**
     * Check if the string is in numeric
     *
     * @param string $str
     * @return bool
     */
    public static function isNumeric(string $str): bool
    {
        return (bool) preg_match('/^[0-9]+(\.[0-9]+)?$/', $str);
    }

    /**
     * Check if the string is in alpha
     *
     * @param string $str
     * @return bool
     */
    public static function isAlpha(string $str): bool
    {
        return (bool) preg_match('/^[a-zA-Z]+$/', $str);
    }

    /**
     * Check if the string is in slug format
     *
     * @param string $str
     * @return bool
     */
    public static function isSlug(string $str): bool
    {
        return (bool) preg_match('/^[a-z0-9-]+[a-z0-9]+$/', $str);
    }

    /**
     * Check if the string is in uppercase
     *
     * @param  string $str
     * @return bool
     */
    public static function isUpper(string $str): bool
    {
        return static::upper($str) === $str;
    }

    /**
     * Check if the string is lowercase
     *
     * @param  string $str
     * @return bool
     */
    public static function isLower(string $str): bool
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
    public static function count(string $pattern, string $str): int
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
    public static function words(string $words, int $len): string
    {
        $wordParts = explode(' ', $words);

        $sentence = '';

        for ($i = 0; $i < $len; $i++) {
            if (!isset($wordParts[$i])) {
                break;
            }

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
    public static function shuffleWords(string $words): string
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
    public static function forceInUTF8(): void
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
    public static function fixUTF8(string $garbled_utf8_string): string
    {
        return Encoding::fixUTF8($garbled_utf8_string);
    }

    /**
     * __call
     *
     * @param string $method
     * @param  array  $arguments
     * @return mixed
     */
    public function __call(string $method, array $arguments = [])
    {
        return call_user_func_array([static::class, $method], $arguments);
    }
}
