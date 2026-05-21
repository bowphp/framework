<?php

declare(strict_types=1);

namespace Bow\Validation\Rules;

trait BooleanRule
{
    /**
     * Compile Boolean Rule
     *
     * [boolean] / [bool] — Accepts true, false, 0, 1, "0", "1", "true", "false".
     * Matches the typical form-input meaning rather than PHP's strict bool type.
     *
     * @param  string $key
     * @param  string $masque
     * @return void
     */
    protected function compileBoolean(string $key, string $masque): void
    {
        if (!preg_match("/^bool(ean)?$/", $masque)) {
            return;
        }

        $value = $this->inputs[$key] ?? null;
        $accepted = [true, false, 0, 1, '0', '1', 'true', 'false'];

        if (in_array($value, $accepted, true)) {
            return;
        }

        $this->fails = true;

        $this->last_message = $this->lexical('boolean', $key);

        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message,
        ];
    }
}
