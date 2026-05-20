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

        if (isset($this->inputs[$key]) && !Str::isEmpty($this->inputs[$key])) {
            return false;
        }

        $this->inputs[$key] = null;

        return true;
    }
}
