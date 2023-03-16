<?php

namespace Bow\Console\Command;

use Bow\Console\Color;
use Bow\Console\ConsoleInformation;
use Bow\Console\Generator;
use Bow\Database\Database;
use Bow\Support\Str;

class SeederCommand extends AbstractCommand
{
    use ConsoleInformation;

    /**
     * Create a seeder
     *
     * @param string $seeder
     */
    public function generate($seeder)
    {
        $generator = new Generator(
            $this->setting->getSeederDirectory(),
            "{$seeder}_seeder"
        );

        if ($generator->fileExists()) {
            echo "\033[0;31mThe seeder already exists.\033[00m";

            exit(1);
        }

        $num = (int)  $this->arg->options()->get('--seed', 5);

        $generator->write('seed', [
            'num' => $num,
            'name' => Str::plurial($seeder)
        ]);

        echo "\033[0;32mThe seeder has been created.\033[00m\n";

        exit(0);
    }

    /**
     * Launch all seeding
     *
     * @return void
     */
    public function all()
    {
        $seeds_filenames = glob($this->setting->getSeederDirectory() . '/*_seeder.php');

        $this->make($seeds_filenames);
    }

    /**
     * Launch targeted seeding
     *
     * @param string $table_name
     *
     * @return void
     */
    public function table($table_name)
    {
        $table_name = trim($table_name);

        if (is_null($table_name)) {
            $this->throwFailsCommand('Specify the seeder table name', 'help seed');
        }

        if (!file_exists($this->setting->getSeederDirectory() . "/{$table_name}_seeder.php")) {
            echo Color::red("Seeder $table_name not exists.");

            exit(1);
        }

        $this->make([
            $this->setting->getSeederDirectory() . "/{$table_name}_seeder.php"
        ]);
    }

    /**
     * Make Seeder
     *
     * @return void
     */
    private function make($seeds_filenames)
    {
        $seed_collection = [];

        foreach ($seeds_filenames as $filename) {
            $seeds = require $filename;

            $seed_collection = array_merge($seeds, $seed_collection);
        }

        $connection = $this->arg->options()->get('--connection', config("database.default"));

        try {
            foreach ($seed_collection as $table => $seed) {
                $n = Database::connection($connection)->table($table)->insert($seed);

                echo Color::green("$n seed" . ($n > 1 ? 's' : '') . " on $table table\n");
            }
        } catch (\Exception $e) {
            echo Color::red($e->getMessage());

            exit(1);
        }
    }
}
