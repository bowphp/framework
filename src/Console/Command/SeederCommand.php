<?php

namespace Bow\Console\Command;

use Bow\Console\Color;
use Bow\Console\GeneratorCommand;
use Bow\Database\Database;

class SeederCommand extends AbstractCommand
{
    /**
     * Create a seeder
     *
     * @param string $name
     */
    public function generate($seeder)
    {
        $generator = new GeneratorCommand(
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
            'name' => $seeder
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
        $seeds_filenames = glob($this->setting->getSeederDirectory().'/*_seeder.php');

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

        if (!file_exists($this->setting->getSeederDirectory()."/{$table_name}_seeder.php")) {
            echo Color::red("Seeder $table_name not exists.");

            exit(1);
        }

        $this->make([
            $this->setting->getSeederDirectory()."/{$table_name}_seeder.php"
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

        try {
            foreach ($seed_collection as $table => $seed) {
                $n = Database::table($table)->insert($seed);

                echo Color::green("$n seed".($n > 1 ? 's' : '')." on $table table\n");
            }
        } catch (\Exception $e) {
            echo Color::red($e->getMessage());

            exit(1);
        }
    }
}
