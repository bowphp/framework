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
            $seeder_files[$seeder_file] = $this->normalizeClassName(explode('.', basename($seeder_file))[0]);
        }

        foreach ($seeder_files as $seeder_file => $seeder_class_name) {
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
        $file_basename = basename($seed_filename);
        try {
            echo Color::green("Seeding: seeders/$file_basename\n");
            include_once $seed_filename;
            (new $seeder_class_name())->run();
            echo Color::green("Seeded: seeders/$file_basename\n");
        } catch (Exception $e) {
            echo Color::red("Seeding failed for: seeders/$file_basename");
            echo Color::red("\n" . $e->getMessage());
        }
    }

    /**
     * Launch targeted seeding
     *
     * @param  string|null $seeder_class_name
     * @return void
     */
    public function file(?string $seeder_class_name = null): void
    {
        if (is_null($seeder_class_name)) {
            $this->throwFailsCommand('Specify the seeder file name', 'help seed');
        }

        $seeder_files = [];

        foreach (glob($this->setting->getSeederDirectory() . '/*.php') as $seeder_file) {
            $interal_class_base_name = $this->normalizeClassName(explode('.', basename($seeder_file))[0]);
            if ($seeder_class_name != $interal_class_base_name) {
                continue;
            }
            $seeder_files[$seeder_file] = $interal_class_base_name;
            break;
        }

        foreach ($seeder_files as $file => $_seeder_class_name) {
            $this->make($file, $_seeder_class_name);
        }
    }

    private function normalizeClassName(string $seeder_class_name): string
    {
        $time = explode('-', $seeder_class_name)[0];
        $seeder_class_name = str_replace($time, '', $seeder_class_name);

        return Str::camel($seeder_class_name) . $time;
    }
}
