<?php

declare(strict_types=1);

namespace Bow\Validation\Rules;

trait NumericRule
{
    /**
     * Compile Number Rule
     *
     * [number] Check that the contents of the field is a number
     *
     * @param string $key
     * @param string $masque
     * @return void
     */
    protected function compileNumber(string $key, string $masque): void
    {
        if (!preg_match("/^number$/", $masque, $match)) {
            return;
        }

        if (is_numeric($this->inputs[$key])) {
            return;
        }

        $this->last_message = $this->lexical('number', $key);

        $this->fails = true;

        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message
        ];
    }

    /**
     * Compile Int Rule
     *
     * [int] Check that the contents of the field is an integer number
     *
     * @param string $key
     * @param string $masque
     * @return void
     */
    protected function compileInt(string $key, string $masque): void
    {
        if (!preg_match("/^int$/", $masque, $match)) {
            return;
        }

        if (is_int($this->inputs[$key])) {
            return;
        }

        $this->last_message = $this->lexical('int', $key);

        $this->fails = true;

        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message
        ];
    }

    /**
     * Compile Float Rule
     *
     * [float] Check that the field content is a float number
     *
     * @param string $key
     * @param string $masque
     * @return void
     */
    protected function compileFloat(string $key, string $masque): void
    {
        if (!preg_match("/^float$/", $masque, $match)) {
            return;
        }

        if (is_float($this->inputs[$key])) {
            return;
        }

        $this->last_message = $this->lexical('float', $key);

        $this->fails = true;

        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message
        ];
    }
}
