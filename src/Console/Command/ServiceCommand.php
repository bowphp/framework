<?php

declare(strict_types=1);

namespace Bow\Console\Command;

use Bow\Console\Generator;

class ServiceCommand extends AbstractCommand
{
    /**
     * Add service
     *
     * @param string $service
     * @return void
     */
    public function generate(string $service): void
    {
        $generator = new Generator(
            $this->setting->getServiceDirectory(),
            $service
        );

        if ($generator->fileExists()) {
            echo "\033[0;31mThe service already exists.\033[00m\n";

            exit(1);
        }

        $generator->write('service', [
            'baseNamespace' => $this->namespaces['service'] ?? 'App\\Services'
        ]);

        echo "\033[0;32mThe service has been well created.\033[00m\n";

        exit(0);
    }
}
