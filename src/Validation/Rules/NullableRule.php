<?php

declare(strict_types=1);

namespace Bow\Validation\Rules;

use Bow\Support\Str;

trait NullableRule
{
    /**
     * Compile Nullable Rule
     *
     * [nullable] Check that the content of the field is nullable
     *
     * @param  string $key
     * @param  string $masque
     * @return bool
     */
    protected function compileNullable(string $key, string $masque): bool
    {
        if (!preg_match("/^nullable$/", $masque, $match)) {
            return false;
        }

        if (!$this->isBlankInput($this->inputs[$key] ?? null)) {
            return false;
        }

        $this->inputs[$key] = null;

        return true;
    }

    /**
     * Whether a submitted value counts as "nothing was sent".
     *
     * Inputs arrive with their native type when the request body is JSON, so
     * false, 0 and 0.0 are real values and must be validated by the rules that
     * follow; only null, a blank string and an empty array are absent.
     *
     * @param  mixed $value
     * @return bool
     */
    private function isBlankInput(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return Str::isEmpty($value);
        }

        if (is_array($value)) {
            return $value === [];
        }

        return false;
    }
}
