<?php

declare(strict_types=1);

namespace Bow\Validation\Rules;

use Bow\Support\Str;

trait EmailRule
{
    /**
     * Compile Email Rule
     *
     * [email] Check that the content of the field is an email
     *
     * @param string $key
     * @param string $masque
     * @return void
     */
    protected function compileEmail(string $key, string $masque): void
    {
        if (!preg_match("/^email$/", $masque, $match)) {
            return;
        }

        if (Str::isMail($this->inputs[$key])) {
            return;
        }

        $this->last_message = $this->lexical('email', $key);

        $this->fails = true;

        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message
        ];
    }
}
