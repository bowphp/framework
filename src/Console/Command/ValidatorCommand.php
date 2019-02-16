<?php

namespace Bow\Console\Command;

use Bow\Console\GeneratorCommand;

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
        $generator = new GeneratorCommand(
            $this->dirname,
            $validator
        );

        if ($generator->fileExists()) {
            echo "\033[0;33mThe validator already exists.\033[00m\n";

            exit(0);
        }

        $generator->write('validator', [
            'baseNamespace' => $this->command->getNamespace('validator')
        ]);

        echo "\033[0;32mThe validator was created well.\033[00m\n";

        exit(0);
    }
}
