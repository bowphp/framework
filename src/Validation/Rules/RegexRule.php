<?php

declare(strict_types=1);

namespace Bow\Validation\Rules;

trait RegexRule
{
    /**
     * Compile Regex Rule
     *
     * Check that the contents of the field with a regular expression
     *
     * @param string $key
     * @param string|int|float $masque
     * @return void
     */
    protected function compileRegex(string $key, string|int|float $masque): void
    {
        if (!preg_match("/^regex:(.+)+$/", (string) $masque, $match)) {
            return;
        }

        $regex = '~' . addcslashes($match[1], "~") . '~';

        if (preg_match($regex, $this->inputs[$key])) {
            return;
        }

        $this->fails = true;

        $this->last_message = $this->lexical('regex', $key);

        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message
        ];
    }
}
