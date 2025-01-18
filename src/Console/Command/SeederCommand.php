<?php

declare(strict_types=1);

namespace Bow\Console\Command;

use Bow\Console\AbstractCommand;
use Bow\Console\Color;
use Bow\Console\Generator;
use Bow\Console\Traits\ConsoleTrait;
use Bow\Database\Database;
use Bow\Support\Str;
use ErrorException;
use JetBrains\PhpStorm\NoReturn;

class SeederCommand extends AbstractCommand
{
    use ConsoleTrait;

    /**
     * Create a seeder
     *
     * @param string $seeder
     */
    #[NoReturn] public function generate(string $seeder): void
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

    /**
     * Launch all seeding
     *
     * @return void
     */
    public function all(): void
    {
        $seeder = $this->setting->getSeederDirectory() . '/_database.php';

        $this->make($seeder);
    }

    /**
     * Launch targeted seeding
     *
     * @param string|null $seeder_name
     * @return void
     */
    public function table(?string $seeder_name = null): void
    {
        if (is_null($seeder_name)) {
            $this->throwFailsCommand('Specify the seeder table name', 'help seed');
        }

        $seeder_name = trim($seeder_name);

        if (!file_exists($this->setting->getSeederDirectory() . "/{$seeder_name}.php")) {
            echo Color::red("Seeder $seeder_name not exists.");

            exit(1);
        }

        $this->make(
            $this->setting->getSeederDirectory() . "/{$seeder_name}.php"
        );
    }

    /**
     * Make Seeder
     *
     * @param string $seed_filename
     * @return void
     */
    private function make(string $seed_filename): void
    {
        $seeds = require $seed_filename;

        $seed_collection = array_merge($seeds);

        // Get the database connexion
        $connection = $this->arg->getParameters()->get('--connection', config("database.default"));

        try {
            $connection = Database::connection($connection);

            foreach ($seed_collection as $table => $seed) {
                if (class_exists($table, true)) {
                    $instance = app($table);
                    if ($instance instanceof \Bow\Database\Barry\Model) {
                        $table = $instance->getTable();
                    }
                }

                $result = $connection->table($table)->insert($seed);

                echo Color::green("$result seed" . ($result > 1 ? 's' : '') . " on $table table\n");
            }
        } catch (\Exception $e) {
            echo Color::red($e->getMessage());

            exit(1);
        }
    }
}
