<?php

declare(strict_types=1);

namespace Bow\Console\Command;

use Exception;
use Bow\Support\Str;
use Bow\Console\Color;
use Bow\Console\AbstractCommand;
use Bow\Console\Traits\ConsoleTrait;

class SeederCommand extends AbstractCommand
{
    use ConsoleTrait;

    /**
     * Launch all seeding
     *
     * @return void
     */
    public function all(): void
    {
        $seeder_files = [];

        foreach (glob($this->setting->getSeederDirectory() . '/*.php') as $seeder_file) {
            $seeder_files[$seeder_file] = explode('.', basename($seeder_file))[0];
        }

        foreach ($seeder_files as $seeder_file => $seeder_class_name) {
            echo Color::green("Seeding: $seeder_file");

            $this->make($seeder_file, $seeder_class_name);
        }
    }

    /**
     * Make Seeder
     *
     * @param  string $seed_filename
     * @return void
     */
    private function make(string $seed_filename, string $seeder_class_name): void
    {
        try {
            include_once $seed_filename;
            $time = explode('-', $seeder_class_name)[0];
            $seeder_class_name = str_replace($time, '', $seeder_class_name);
            $seeder_class_name = Str::camel($seeder_class_name);
            (new $seeder_class_name())->run();
        } catch (Exception $e) {
            echo Color::red($e->getMessage());
            echo Color::red("Seeding failed for: $seed_filename");
        }
    }

    /**
     * Launch targeted seeding
     *
     * @param  string|null $seeder_name
     * @return void
     */
    public function file(?string $seeder_class_name = null): void
    {
        if (is_null($seeder_class_name)) {
            $this->throwFailsCommand('Specify the seeder file name', 'help seed');
        }

        $seeder_file = [];

        foreach (glob($this->setting->getSeederDirectory() . '/*.php') as $seeder_file) {
            $basename = explode('.', basename($seeder_file))[0];
            if ($seeder_class_name != $basename) {
                continue;
            }
            $seeder_file[$seeder_file] = explode('.', basename($seeder_file))[0];
            break;
        }

        foreach ($seeder_file as $seeder_file => $seeder_class_name) {
            echo Color::green("Seeding: $seeder_file");

            $this->make($seeder_file, $seeder_class_name);
        }
    }
}
