<?php

declare(strict_types=1);

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
    public function generate(string $producer): void
    {
        $generator = new Generator(
            $this->setting->getProducerDirectory(),
            $producer
        );

        if ($generator->fileExists()) {
            echo Color::red("The producer already exists");
            exit(1);
        }

        $generator->write('producer', [
            'baseNamespace' => $this->namespaces['producer'] ?? 'App\\Producers'
        ]);

        echo Color::green("The producer has been well created.");
        exit(0);
    }
}
