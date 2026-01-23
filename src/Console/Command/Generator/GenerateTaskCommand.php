<?php

declare(strict_types=1);

namespace Bow\Console\Command\Generator;

use Bow\Console\AbstractCommand;
use Bow\Console\Color;
use Bow\Console\Generator;

class GenerateTaskCommand extends AbstractCommand
{
    /**
     * Add task
     *
     * @param  string $task
     * @return void
     */
    public function run(string $task): void
    {
        $generator = new Generator(
            $this->setting->getTaskDirectory(),
            $task
        );

        if ($generator->fileExists()) {
            echo Color::red("The task already exists");
            exit(1);
        }

        $generator->write('task', [
            'baseNamespace' => $this->namespaces['task'] ?? 'App\\Tasks'
        ]);

        echo Color::green("The task has been well created.");
        exit(0);
    }
}
