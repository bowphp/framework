<?php

namespace Bow\Console\Command;

use Bow\Console\Color;
use Bow\Console\Generator;

class ValidatorCommand extends AbstractCommand
{
    /**
     * Add validator
     *
     * @param string $validator
     *
     * @return int
     */
    public function generate($validator)
    {
        $generator = new Generator(
            $this->setting->getValidationDirectory(),
            $validator
        );

        if ($generator->fileExists()) {
            echo Color::red('The validator already exists.');

            exit(0);
        }

        $generator->write('validator', [
            'baseNamespace' => $this->namespaces['validation']
        ]);

        echo Color::green('The validator was created well.');

        exit(0);
    }
}
