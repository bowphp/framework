<?php

declare(strict_types=1);

namespace Bow\Console\Command\Generator;

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
        $create_at = date("YmdHis");
        $class_name = sprintf("%s%s", ucfirst(Str::camel($seeder)), $create_at);
        $filename = sprintf("%s-%s", $create_at, $seeder);

        $generator = new Generator(
            $this->setting->getSeederDirectory(),
            $filename
        );

        if ($generator->fileExists()) {
            echo "\033[0;31mThe seeder {$this->setting->getSeederDirectory()}/{$filename}.php already exists.\033[00m";

            exit(1);
        }

        $generator->write('seeder', ['className' => $class_name]);

        echo "\033[0;32mThe seeder {$this->setting->getSeederDirectory()}/{$filename}.php has been created.\033[00m\n";

        exit(0);
    }
}
