<?php

declare(strict_types=1);

namespace Bow\Validation\Rules;

trait DifferentRule
{
    /**
     * Compile Different Rule
     *
     * [different:other_field] Check that the field's value is different from
     * another field's value. Strict comparison; missing fields are treated as
     * null (so a present field is automatically "different" from a missing one).
     *
     * @param  string $key
     * @param  string $masque
     * @return void
     */
    protected function compileDifferent(string $key, string $masque): void
    {
        if (!preg_match("/^different:(.+)$/", $masque, $match)) {
            return;
        }

        $other_key = trim($match[1]);
        $value = $this->inputs[$key] ?? null;
        $other = $this->inputs[$other_key] ?? null;

        if ($value !== $other) {
            return;
        }

        $this->fails = true;

        $this->last_message = $this->lexical('different', [
            'attribute' => $key,
            'other'     => $other_key,
        ]);

        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message,
        ];
    }
}
