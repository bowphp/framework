<?php

namespace Bow\Console\Command;

use Bow\Console\Color;
use Bow\Console\Generator;

class ProducerCommand extends AbstractCommand
{
    /**
     * Add producer
     *
     * @param string $producer
     * @return void
     */
    public function generate(string $producer)
    {
        $generator = new Generator(
            $this->setting->getProducerDirectory(),
            $producer
        );

        if ($generator->fileExists()) {
            echo "\033[0;31mThe producer already exists.\033[00m\n";

            exit(1);
        }

        $generator->write('producer', [
            'baseNamespace' => $this->namespaces['producer'] ?? 'App\\Producers'
        ]);

        echo "\033[0;32mThe producer has been well created.\033[00m\n";

        exit(0);
    }
}
