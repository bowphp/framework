<?php

declare(strict_types=1);

namespace Bow\Validation\Rules;

trait JsonRule
{
    /**
     * Compile Json Rule
     *
     * [json] Check that the contents of the field is a syntactically valid
     * JSON string.
     *
     * @param  string $key
     * @param  string $masque
     * @return void
     */
    protected function compileJson(string $key, string $masque): void
    {
        if (!preg_match("/^json$/", $masque)) {
            return;
        }

        $value = $this->inputs[$key] ?? null;

        if (is_string($value) && $value !== '') {
            json_decode($value);
            if (json_last_error() === JSON_ERROR_NONE) {
                return;
            }
        }

        $this->fails = true;

        $this->last_message = $this->lexical('json', $key);

        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message,
        ];
    }
}
