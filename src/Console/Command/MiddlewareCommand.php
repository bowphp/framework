<?php

declare(strict_types=1);

namespace Bow\Console\Command;

use Bow\Console\Color;
use Bow\Console\Generator;

class MiddlewareCommand extends AbstractCommand
{
    /**
     * Add middleware
     *
     * @param string $middleware
     * @return void
     */
    public function generate(string $middleware): void
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
