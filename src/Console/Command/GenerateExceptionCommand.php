<?php

declare(strict_types=1);

namespace Bow\Console\Command;

use Bow\Console\AbstractCommand;
use Bow\Console\Generator;

class GenerateExceptionCommand extends AbstractCommand
{
    /**
     * Add middleware
     *
     * @param  string $exception
     * @return void
     */
    public function run(string $exception): void
    {
        $generator = new Generator(
            $this->setting->getExceptionDirectory(),
            $exception
        );

        if ($generator->fileExists()) {
            echo "\033[0;31mThe exception already exists.\033[00m\n";

            exit(1);
        }

        $generator->write('exception', [
            'baseNamespace' => $this->namespaces['exception'] ?? 'App\\Exceptions'
        ]);

        echo "\033[0;32mThe exception has been well created.\033[00m\n";

        exit(0);
    }
}
