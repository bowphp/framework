<?php

declare(strict_types=1);

namespace Bow\Validation\Rules;

trait ConfirmedRule
{
    /**
     * Compile Confirmed Rule
     *
     * [confirmed] Check that the field matches a sibling field named
     * `<key>_confirmation`. Common pattern for password / email confirmation.
     *
     * @param  string $key
     * @param  string $masque
     * @return void
     */
    protected function compileConfirmed(string $key, string $masque): void
    {
        if (!preg_match("/^confirmed$/", $masque)) {
            return;
        }

        $confirmation_key = $key . '_confirmation';
        $value = $this->inputs[$key] ?? null;
        $confirmation = $this->inputs[$confirmation_key] ?? null;

        if ($value === $confirmation) {
            return;
        }

        $this->fails = true;

        $this->last_message = $this->lexical('confirmed', $key);

        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message,
        ];
    }
}
