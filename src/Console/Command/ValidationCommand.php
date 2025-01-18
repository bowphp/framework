<?php

declare(strict_types=1);

namespace Bow\Console\Command;

use Bow\Console\AbstractCommand;
use Bow\Console\Color;
use Bow\Console\Generator;
use JetBrains\PhpStorm\NoReturn;

class ValidationCommand extends AbstractCommand
{
    /**
     * Add validation
     *
     * @param string $validation
     * @return void
     */
    #[NoReturn] public function generate(string $validation): void
    {
        $generator = new Generator(
            $this->setting->getValidationDirectory(),
            $validation
        );

        if ($generator->fileExists()) {
            echo Color::red('The validation already exists.');

            exit(0);
        }

        $generator->write('validation', [
            'baseNamespace' => $this->namespaces['validation']
        ]);

        echo Color::green('The validation was created well.');

        exit(0);
    }
}
