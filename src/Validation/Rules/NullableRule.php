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
     * @return void
     */
    protected function compileNullable(string $key, string $masque): void
    {
        if (!preg_match("/^nullable$/", $masque, $match)) {
            return;
        }

        if (isset($this->inputs[$key]) && !Str::isEmpty($this->inputs[$key])) {
            return;
        }

        $this->inputs[$key] = null;
    }
}
