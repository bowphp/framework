<?php

namespace Bow\Console\Command;

use Bow\Console\Color;
use Bow\Console\Generator;
use Bow\Support\Str;

class GenerateSessionCommand extends AbstractCommand
{
    /**
     * Generate Key
     *
     * @return void
     */
    public function generate()
    {
        $create_at = date("YmdHis");
        $filename = sprintf("Version%s%s", $create_at, ucfirst(Str::camel('sessions')));

        $generator = new Generator(
            $this->setting->getMigrationDirectory(),
            $filename
        );

        $generator->write('model/session', [
            'className' => $filename
        ]);

        echo Color::green('Session migration created.');
    }
}
