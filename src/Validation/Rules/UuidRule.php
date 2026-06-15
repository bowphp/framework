<?php

declare(strict_types=1);

namespace Bow\Validation\Rules;

trait UuidRule
{
    /**
     * Compile Uuid Rule
     *
     * [uuid] Check that the contents of the field is a canonical RFC 4122
     * UUID (versions 1–5).
     *
     * @param  string $key
     * @param  string $masque
     * @return void
     */
    protected function compileUuid(string $key, string $masque): void
    {
        if (!preg_match("/^uuid$/", $masque)) {
            return;
        }

        $value = (string) ($this->inputs[$key] ?? '');
        $pattern = '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';

        if (preg_match($pattern, $value)) {
            return;
        }

        $this->fails = true;

        $this->last_message = $this->lexical('uuid', $key);

        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message,
        ];
    }
}
