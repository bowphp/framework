<?php

declare(strict_types=1);

namespace Bow\Console\Command;

use Bow\Console\AbstractCommand;
use Bow\Console\Generator;

class GenerateConsoleCommand extends AbstractCommand
{
    /**
     * Add service
     *
     * @param  string $service
     * @return void
     */
    public function run(string $service): void
    {
        $generator = new Generator(
            $this->setting->getCommandDirectory(),
            $service
        );

        if ($generator->fileExists()) {
            echo "\033[0;31mThe command already exists.\033[00m\n";

            exit(1);
        }

        $generator->write('command', [
            'baseNamespace' => $this->namespaces['command'] ?? 'App\\Commands'
        ]);

        echo "\033[0;32mThe command has been well created.\033[00m\n";

        exit(0);
    }
}
