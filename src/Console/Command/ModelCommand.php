<?php

namespace Bow\Console\Command;

use Bow\Console\Generator;

class ModelCommand extends AbstractCommand
{
    /**
     * Add Model
     *
     * @param string $model
     *
     * @return mixed
     */
    public function generate($model)
    {
        $generator = new Generator(
            $this->setting->getModelDirectory(),
            $model
        );

        if ($generator->fileExists()) {
            echo "\033[0;33mThe model already exists.\033[00m\n";

            exit(1);
        }

        $generator->write('model/model', [
            'baseNamespace' => $this->namespaces['model']
        ]);

        echo "\033[0;32mThe model was well created.\033[00m\n";

        if ($this->arg->options('-m')) {
            $this->migration->add('create_'.strtolower($model).'_table');
        }

        exit 0;
    }
}
