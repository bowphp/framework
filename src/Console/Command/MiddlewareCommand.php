<?php

declare(strict_types=1);

namespace Bow\Console\Command;

use Bow\Console\AbstractCommand;
use Bow\Console\Color;
use Bow\Console\Generator;
use JetBrains\PhpStorm\NoReturn;

class MiddlewareCommand extends AbstractCommand
{
    /**
     * Add middleware
     *
     * @param string $middleware
     * @return void
     */
    #[NoReturn] public function generate(string $middleware): void
    {
        $generator = new Generator(
            $this->setting->getMiddlewareDirectory(),
            $middleware
        );

        if ($generator->fileExists()) {
            echo Color::red("The middleware already exists");

            exit(1);
        }

        $generator->write('middleware', [
            'baseNamespace' => $this->namespaces['middleware'] ?? "App\\Middlewares"
        ]);

        echo Color::green("The middleware has been well created.");

        exit(0);
    }
}
