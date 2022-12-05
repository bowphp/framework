<?php

declare(strict_types=1);

namespace Bow\Console\Command;

use Bow\Console\Generator;

class AppEventCommand extends AbstractCommand
{
    /**
     * Add event
     *
     * @param string $event
     * @return void
     */
    public function generate(string $event): void
    {
        $generator = new Generator(
            $this->setting->getEventDirectory(),
            $event
        );

        if ($generator->fileExists()) {
            echo "\033[0;31mThe event already exists.\033[00m\n";

            exit(1);
        }

        $generator->write('event', [
            'baseNamespace' => $this->namespaces['event'] ?? 'App\\Events'
        ]);

        echo "\033[0;32mThe event has been well created.\033[00m\n";

        exit(0);
    }
}
