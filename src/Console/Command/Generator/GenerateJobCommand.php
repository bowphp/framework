<?php

declare(strict_types=1);

namespace Bow\Console\Command\Generator;

use Bow\Console\AbstractCommand;
use Bow\Console\Color;
use Bow\Console\Generator;

class GenerateJobCommand extends AbstractCommand
{
    /**
     * Add job
     *
     * @param  string $job
     * @return void
     */
    public function run(string $job): void
    {
        $generator = new Generator(
            $this->setting->getJobDirectory(),
            $job
        );

        if ($generator->fileExists()) {
            echo Color::red("The job already exists");
            exit(1);
        }

        $generator->write('job', [
            'baseNamespace' => $this->namespaces['job'] ?? 'App\\Jobs'
        ]);

        echo Color::green("The job has been well created.");
        exit(0);
    }
}
