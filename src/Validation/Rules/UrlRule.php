<?php

declare(strict_types=1);

namespace Bow\Validation\Rules;

trait UrlRule
{
    /**
     * Compile Url Rule
     *
     * [url] Check that the contents of the field is a well-formed URL.
     *
     * @param  string $key
     * @param  string $masque
     * @return void
     */
    protected function compileUrl(string $key, string $masque): void
    {
        if (!preg_match("/^url$/", $masque)) {
            return;
        }

        if (filter_var($this->inputs[$key] ?? '', FILTER_VALIDATE_URL)) {
            return;
        }

        $this->fails = true;

        $this->last_message = $this->lexical('url', $key);

        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message,
        ];
    }
}
