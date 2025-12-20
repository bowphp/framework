<?php

declare(strict_types=1);

namespace Bow\Console\Command\Generator;

use Bow\Console\AbstractCommand;
use Bow\Console\Color;
use Bow\Console\Generator;
use Bow\Support\Str;

class GenerateMigrationCommand extends AbstractCommand
{
    /**
     * Create a migration command
     *
     * @param string $model
     *
     * @return void
     * @throws ErrorException
     */
    public function run(string $model): void
    {
        $create_at = date("YmdHis");
        $filename = sprintf("Version%s%s", $create_at, ucfirst(Str::camel($model)));

        $generator = new Generator(
            $this->setting->getMigrationDirectory(),
            $filename
        );

        $parameters = $this->arg->getParameters();

        if ($parameters->has('--create') && $parameters->has('--table')) {
            $this->throwFailsCommand('bad command', 'add help');
        }

        $type = "model/standard";

        if ($parameters->has('--table')) {
            if ($parameters->get('--table') === true) {
                $this->throwFailsCommand('bad command option [--table=table]', 'add help');
            }

            $table = $parameters->get('--table');

            $type = 'model/table';
        } elseif ($parameters->has('--create')) {
            if ($parameters->get('--create') === true) {
                $this->throwFailsCommand('bad command option [--create=table]', 'add help');
            }

            $table = $parameters->get('--create');

            $type = 'model/create';
        }

        $generator->write($type, [
            'table' => $table ?? 'table_name',
            'className' => $filename
        ]);

        // Print console information
        echo Color::green("The migration {$filename} file has been successfully created") . "\n";
    }
}
