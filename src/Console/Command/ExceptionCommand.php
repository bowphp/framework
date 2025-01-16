<?php

declare(strict_types=1);

namespace Bow\Console\Command;

use Bow\Console\AbstractCommand;
use Bow\Console\Generator;
use JetBrains\PhpStorm\NoReturn;

class ExceptionCommand extends AbstractCommand
{
    /**
     * Add middleware
     *
     * @param string $exception
     * @return void
     */
    #[NoReturn] public function generate(string $exception): void
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
