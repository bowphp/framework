<?php

declare(strict_types=1);

namespace Bow\Validation\Rules;

trait IpRule
{
    /**
     * Compile Ip Rule
     *
     * [ip], [ip:v4], [ip:v6] — Check that the contents of the field is a valid
     * IPv4/IPv6 address. With no suffix either version is accepted.
     *
     * @param  string $key
     * @param  string $masque
     * @return void
     */
    protected function compileIp(string $key, string $masque): void
    {
        if (!preg_match("/^ip(?::(v4|v6))?$/", $masque, $match)) {
            return;
        }

        $flags = match ($match[1] ?? null) {
            'v4'    => FILTER_FLAG_IPV4,
            'v6'    => FILTER_FLAG_IPV6,
            default => 0,
        };

        if (filter_var($this->inputs[$key] ?? '', FILTER_VALIDATE_IP, $flags)) {
            return;
        }

        $this->fails = true;

        $this->last_message = $this->lexical('ip', $key);

        $this->errors[$key][] = [
            "masque" => $masque,
            "message" => $this->last_message,
        ];
    }
}
