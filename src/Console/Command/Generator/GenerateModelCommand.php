<?php

declare(strict_types=1);

namespace Bow\Console\Command\Generator;

use Bow\Console\AbstractCommand;
use Bow\Console\Color;
use Bow\Console\Generator;

class GenerateModelCommand extends AbstractCommand
{
    /**
     * Add Model
     *
     * @param  string $model
     * @return void
     */
    public function run(string $model): void
    {
        $generator = new Generator(
            $this->setting->getModelDirectory(),
            $model
        );

        if ($generator->fileExists()) {
            echo Color::red('The model already exists.');

            exit(1);
        }

        $generator->write('model/model', [
            'baseNamespace' => $this->namespaces['model']
        ]);

        echo Color::green("The model was well created.");
    }
}
