<?php

declare(strict_types=1);

namespace Bow\Console\Command;

use Bow\Console\AbstractCommand;
use Bow\Console\Generator;
use Bow\Console\Traits\ConsoleTrait;
use Bow\Support\Str;

class GenerateSeederCommand extends AbstractCommand
{
    use ConsoleTrait;

    /**
     * Create a seeder
     *
     * @param string $seeder
     */
    public function run(string $seeder): void
    {
        $seeder = Str::plural($seeder);

        $generator = new Generator(
            $this->setting->getSeederDirectory(),
            $seeder
        );

        if ($generator->fileExists()) {
            echo "\033[0;31mThe seeder already exists.\033[00m";

            exit(1);
        }

        $generator->write('seeder', ['name' => $seeder]);

        echo "\033[0;32mThe seeder has been created.\033[00m\n";

        exit(0);
    }
}
