<?php

namespace Bow\Console\Command;

use Bow\Console\Color;
use Bow\Console\Generator;

class ConfigurationCommand extends AbstractCommand
{
    /**
     * Add configuration
     *
     * @param string $configuration
     * @return void
     */
    public function generate(string $configuration): void
    {
        $generator = new Generator(
            $this->setting->getPackageDirectory(),
            $configuration
        );

        if ($generator->fileExists()) {
            echo Color::red('The configuration already exists.');

            exit(0);
        }

        $generator->write('configuration', [
            'baseNamespace' => $this->namespaces['configuration']
        ]);

        echo Color::green('The configuration was well created.');

        exit(0);
    }
}
