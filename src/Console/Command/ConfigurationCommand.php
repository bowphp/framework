<?php

declare(strict_types=1);

namespace Bow\Console\Command;

use Bow\Console\AbstractCommand;
use Bow\Console\Color;
use Bow\Console\Generator;
use JetBrains\PhpStorm\NoReturn;

class ConfigurationCommand extends AbstractCommand
{
    /**
     * Add configuration
     *
     * @param string $configuration
     * @return void
     */
    #[NoReturn] public function generate(string $configuration): void
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
