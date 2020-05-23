<?php

namespace Bow\Console\Command;

use Bow\Console\Color;
use Bow\Console\ConsoleInformation;

class ServiceCommand extends AbstractCommand
{
    /**
     * Add middleware
     *
     * @param string $middleware
     *
     * @return void
     */
    public function generate(string $service)
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
