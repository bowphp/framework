<?php

namespace Bow\Console\Command;

use Bow\Console\Generator;

class ControllerCommand extends AbstractCommand
{
    /**
     * The add controller command
     *
     * @param string $controller
     * @return void
     */
    public function generate(string $controller): void
    {
        $generator = new Generator(
            $this->setting->getControllerDirectory(),
            $controller
        );

        if ($generator->fileExists()) {
            echo "\033[0;31mThe controller already exists.\033[00m\n";

            exit(1);
        }

        if ($this->arg->getParameter('--no-plain')) {
            $generator->write('controller/no-plain', [
                'baseNamespace' => $this->namespaces['controller']
            ]);
        } else {
            $generator->write('controller/controller', [
                'baseNamespace' => $this->namespaces['controller']
            ]);
        }

        echo "\033[0;32mThe controller was well created.\033[00m\n";

        exit(0);
    }
}
