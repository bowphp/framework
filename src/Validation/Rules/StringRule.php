<?php

declare(strict_types=1);

namespace Bow\Validation\Rules;

use Bow\Support\Str;
use Bow\Validation\Exception\ValidationException;

trait StringRule
{
    /**
     * Compile Required Rule
     *
     * @param string $key
     * @param string $masque
     * @return void
     */
    protected function compileRequired(string $key, string $masque): void
    {
        $error = false;

        if (!preg_match("/^required$/", (string) $masque, $match)) {
            return;
        }

        if (!isset($this->inputs[$key])) {
            $error = true;
        }

        if (isset($this->inputs[$key]) && (is_null($this->inputs[$key]) || $this->inputs[$key] === '')) {
            $error = true;
        }

        if ($error) {
            $this->last_message = $message = $this->lexical('required', $key);

            $this->errors[$key][] = [
                "masque" => $masque,
                "message" => $message
            ];

            $this->fails = true;
        }
    }

    /**
     * Compile Required Rule
     *
     * @param string $key
     * @param string $masque
     * @return void
     */
    protected function compileRequiredIf(string $key, string $masque): void
    {
        $error = false;
        $exists = false;

        if (!preg_match("/^required_if:(.+)+$/", (string) $masque, $match)) {
            throw new ValidationException("The required_if is malformed");
        }

        array_shift($match);

        if (count($match) < 1) {
            throw new ValidationException("The required_if is malformed");
        }

        foreach ($match as $key => $present) {
            if ($key == 0) {
                $exists = isset($this->inputs[$present]);
            } else {
                $exists = $exists && isset($this->inputs[$present]);
            }
        }

        if ($exists && !isset($this->inputs[$key])) {
            $error = true;
        }

        if (!$error && isset($this->inputs[$key]) && (is_null($this->inputs[$key]) || $this->inputs[$key] === '')) {
            $error = true;
        }

        if ($error) {
            $this->last_message = $message = $this->lexical('required_if', $key);

            $this->errors[$key][] = [
                "masque" => $masque,
                "message" => $message
            ];

            $this->fails = true;
        }
    }

    /**
     * Compile Empty Rule
     *
     * @param string $key
     * @param string $masque
     * @return void
     */
    protected function compileEmpty(string $key, string $masque): void
    {
        if (isset($this->inputs[$key]) && !(is_null($this->inputs[$key]) || $this->inputs[$key] === '')) {
            $this->fails = true;

            $this->last_message = $message = $this->lexical('empty', $key);

            $this->errors[$key][] = [
                "masque" => $masque,
                "message" => $message
            ];
        }
    }

    /**
     * Compile Alphanum Rule
     *
     * [alphanum] Check that the field content is an alphanumeric string
     *
     * @param string $key
     * @param string $masque
     * @return void
     */
    protected function compileAlphaNum(string $key, string $masque): void
    {
        if (!preg_match("/^alphanum$/", $masque)) {
            return;
        }

        if (Str::isAlphaNum($this->inputs[$key])) {
            return;
        }

        $this->last_message = $this->lexical('alphanum', $key);

        $this->fails = true;

        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message
        ];
    }

    /**
     * Compile In Rule
     *
     * [in:(value, ...)] Check that the contents of the field are equal to the defined value
     *
     * @param string $key
     * @param string $masque
     * @return void
     */
    protected function compileIn(string $key, string $masque): void
    {
        if (!preg_match("/^in:(.+)$/", $masque, $match)) {
            return;
        }

        $values = explode(",", end($match));

        foreach ($values as $index => $value) {
            $values[$index] = trim($value);
        }

        if (in_array($this->inputs[$key], $values)) {
            return;
        }

        $this->last_message = $this->lexical('in', [
            'attribute' => $key,
            'value' => implode(", ", $values)
        ]);

        $this->fails = true;

        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message
        ];
    }

    /**
     * Compile Size Rule
     *
     * [size:value] Check that the contents of the field is a number
     * of character equal to the defined value
     *
     * @param string $key
     * @param string $masque
     * @return void
     */
    protected function compileSize(string $key, string $masque): void
    {
        if (!preg_match("/^size:(\d+)$/", $masque, $match)) {
            return;
        }

        $length = (int) end($match);

        if (Str::len($this->inputs[$key]) == $length) {
            return;
        }

        $this->fails = true;

        $this->last_message = $this->lexical('size', [
            'attribute' => $key,
            'length' => $length
        ]);

        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message
        ];
    }

    /**
     * Compile Lower Rule
     *
     * [lower] Check that the content of the field is a string in miniscule
     *
     * @param string $key
     * @param string $masque
     * @return void
     */
    protected function compileLower(string $key, string $masque): void
    {
        if (!preg_match("/^lower/", $masque)) {
            return;
        }

        if (Str::isLower($this->inputs[$key])) {
            return;
        }

        $this->fails = true;

        $this->last_message = $this->lexical('lower', $key);

        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message
        ];
    }

    /**
     * Compile Upper Rule
     *
     * [upper] Check that the contents of the field is a string in uppercase
     *
     * @param string $key
     * @param string $masque
     * @return void
     */
    protected function compileUpper(string $key, string $masque): void
    {
        if (!preg_match("/^upper/", $masque)) {
            return;
        }

        if (Str::isUpper($this->inputs[$key])) {
            return;
        }

        $this->fails = true;

        $this->last_message = $this->lexical('upper', $key);

        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message
        ];
    }

    /**
     * Compile Alpha Rule
     *
     * [alpha] Check that the field content is an alpha
     *
     * @param string $key
     * @param string $masque
     * @return void
     */
    protected function compileAlpha(string $key, string $masque): void
    {
        if (!preg_match("/^alpha$/", $masque)) {
            return;
        }

        if (Str::isAlpha($this->inputs[$key])) {
            return;
        }

        $this->last_message = $this->lexical('alpha', $key);

        $this->fails = true;

        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message
        ];
    }

    /**
     * Compile Min Mask
     *
     * [min:value] Check that the content of the field is a number of
     * minimal character following the defined value
     *
     * @param string $key
     * @param string $masque
     * @return void
     */
    protected function compileMin(string $key, string $masque): void
    {
        if (!preg_match("/^min:(\d+)$/", $masque, $match)) {
            return;
        }

        $length = (int) end($match);

        if (Str::len($this->inputs[$key]) >= $length) {
            return;
        }

        $this->fails = true;

        $this->last_message = $this->lexical('min', [
            'attribute' => $key,
            'length' => $length
        ]);

        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message
        ];
    }

    /**
     * Compile Max Rule
     *
     * [max:value] Check that the content of the field is a number of
     * maximum character following the defined value
     *
     * @param string $key
     * @param string $masque
     * @return void
     */
    protected function compileMax(string $key, string $masque): void
    {
        if (!preg_match("/^max:(\d+)$/", $masque, $match)) {
            return;
        }

        $length = (int) end($match);

        if (Str::len($this->inputs[$key]) <= $length) {
            return;
        }

        $this->fails = true;

        $this->last_message = $this->lexical('max', [
            'attribute' => $key,
            'length' => $length
        ]);

        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message
        ];
    }

    /**
     * Compile Some Rule
     *
     * [same:value] Check that the field contents are equal to the mask value
     *
     * @param string $key
     * @param string $masque
     * @return void
     */
    protected function compileSame(string $key, string $masque): void
    {
        if (!preg_match("/^same:(.+)$/", $masque, $match)) {
            return;
        }

        $value = (string) end($match);

        if ($this->inputs[$key] == $value) {
            return;
        }

        $this->last_message = $this->lexical('same', [
            'attribute' => $key,
            'value' => $value
        ]);

        $this->fails = true;
        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message
        ];
    }
}
