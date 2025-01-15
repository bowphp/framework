<?php

declare(strict_types=1);

namespace Bow\Console\Command;

use Bow\Console\AbstractCommand;
use Bow\Console\Generator;
use JetBrains\PhpStorm\NoReturn;

class EventListenerCommand extends AbstractCommand
{
    /**
     * Add event
     *
     * @param string $event
     * @return void
     */
    #[NoReturn] public function generate(string $event): void
    {
        $generator = new Generator(
            $this->setting->getEventListenerDirectory(),
            $event
        );

        if ($generator->fileExists()) {
            echo "\033[0;31mThe event already exists.\033[00m\n";

            exit(1);
        }

        $generator->write('listener', [
            'baseNamespace' => $this->namespaces['listener'] ?? 'App\\Listeners'
        ]);

        echo "\033[0;32mThe event has been well created.\033[00m\n";

        exit(0);
    }
}
