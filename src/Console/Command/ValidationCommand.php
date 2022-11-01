<?php

namespace Bow\Console\Command;

use Bow\Console\Color;
use Bow\Console\Generator;

class ValidationCommand extends AbstractCommand
{
    /**
     * Add validation
     *
     * @param string $validation
     * @return void
     */
    public function generate(string $validation): void
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
