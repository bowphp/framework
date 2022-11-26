<?php

declare(strict_types=1);

namespace Bow\Validation\Rules;

trait RegexRule
{
    /**
     * Compile Regex Rule
     *
     * [regex] Check that the contents of the field with a regular expression
     *
     * @param string $key
     * @param string $masque
     * @return void
     */
    protected function compileRegex(string $key, string $masque): void
    {
        if (!preg_match("/^regex:(.+)+$/", $masque, $match)) {
            return;
        }

        $regex = '~^' . $match[1] . '$~';

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
