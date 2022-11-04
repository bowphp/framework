<?php

declare(strict_types=1);

namespace Bow\Console\Command;

use Bow\Support\Str;
use Bow\Console\Color;
use Bow\Console\Generator;
use Bow\Database\Database;
use Bow\Console\Traits\ConsoleTrait;

class SeederCommand extends AbstractCommand
{
    use ConsoleTrait;
    
    /**
     * Create a seeder
     *
     * @param string $seeder
     */
    public function generate(string $seeder): void
    {
        $generator = new Generator(
            $this->setting->getSeederDirectory(),
            "{$seeder}_seeder"
        );

        if ($generator->fileExists()) {
            echo "\033[0;31mThe seeder already exists.\033[00m";

            exit(1);
        }

        // Get the number of execution
        $num = (int) $this->arg->getParameters()->get('--seed', 5);

        $generator->write('seeder', [
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
    public function all(): void
    {
        $seeds_filenames = glob($this->setting->getSeederDirectory().'/*_seeder.php');

        $this->make($seeds_filenames);
    }

    /**
     * Launch targeted seeding
     *
     * @param string $table_name
     * @return void
     */
    public function table(string $table_name): void
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
     * @param array $seeds_filenames
     * @return void
     */
    private function make(array $seeds_filenames): void
    {
        $seed_collection = [];

        foreach ($seeds_filenames as $filename) {
            $seeds = require $filename;

            $seed_collection = array_merge($seeds, $seed_collection);
        }

        // Get the database connexion
        $connection = $this->arg->getParameters()->get('--connection', config("database.default"));

        try {
            foreach ($seed_collection as $table => $seed) {
                $result = Database::connection($connection)->table($table)->insert($seed);

                echo Color::green("$result seed".($result > 1 ? 's' : '')." on $table table\n");
            }
        } catch (\Exception $e) {
            echo Color::red($e->getMessage());

            exit(1);
        }
    }
}
