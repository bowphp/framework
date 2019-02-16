<?php

namespace Bow\Console\Command;

use Bow\Console\Generator;

class MiddlewareCommand extends AbstractCommand
{
    /**
     * Add middleware
     *
     * @param string $middleware
     *
     * @return void
     */
    public function generate($middleware)
    {
        $generator = new Generator(
            $this->setting->getMiddlewareDirectory(),
            $middleware
        );

        if ($generator->fileExists()) {
            echo "\033[0;31mThe middleware already exists.\033[00m\n";

            exit(1);
        }

        $generator->write('middleware', [
            'baseNamespace' => $this->namespaces['middleware']
        ]);

        echo "\033[0;32mThe middleware has been well created.\033[00m\n";

        exit(0);
    }
}
