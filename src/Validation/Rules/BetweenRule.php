<?php

declare(strict_types=1);

namespace Bow\Validation\Rules;

use Bow\Support\Str;

trait BetweenRule
{
    /**
     * Compile Between Rule
     *
     * [between:min,max] Check that the value is between min and max (inclusive).
     * For numeric values the numeric value is checked; for strings the
     * character length is checked — mirroring the convention used by `min`
     * and `max`.
     *
     * @param  string $key
     * @param  string $masque
     * @return void
     */
    protected function compileBetween(string $key, string $masque): void
    {
        if (!preg_match('/^between:(\d+),(\d+)$/', $masque, $match)) {
            return;
        }

        $min = (int) $match[1];
        $max = (int) $match[2];
        $value = $this->inputs[$key] ?? null;

        $size = match (true) {
            is_int($value) || is_float($value) => $value,
            is_numeric($value)                  => +$value,
            is_string($value)                   => Str::len($value),
            default                             => null,
        };

        if ($size !== null && $size >= $min && $size <= $max) {
            return;
        }

        $this->fails = true;

        $this->last_message = $this->lexical('between', [
            'attribute' => $key,
            'min'       => $min,
            'max'       => $max,
        ]);

        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message,
        ];
    }
}
