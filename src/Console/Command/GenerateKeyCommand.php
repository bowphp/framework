<?php

declare(strict_types=1);

namespace Bow\Console\Command;

use Bow\Console\Color;
use Bow\Console\Exception\ConsoleException;

class GenerateKeyCommand extends AbstractCommand
{
    /**
     * Generate Key
     *
     * @return void
     */
    public function generate(): void
    {
        $key = base64_encode(openssl_random_pseudo_bytes(12) . date('Y-m-d H:i:s') . microtime(true));

        $env_file = config('app.env_file');

        if (!file_exists($env_file)) {
            throw new ConsoleException("The .env.json file not found. Run cp .env.example.json .env.json");
        }

        $contents = file_get_contents($env_file);
        $contents = preg_replace('@"APP_KEY"\s*:\s*".+?"@', '"APP_KEY": "' . $key . '"', $contents);

        file_put_contents($env_file, $contents);

        echo sprintf("Application key => %s\n", Color::green($key));

        exit;
    }
}
