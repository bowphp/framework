<?php

declare(strict_types=1);

namespace Bow\Console\Command;

use Bow\Console\Color;
use Bow\Console\Generator;
use Bow\Support\Str;

class GenerateCacheCommand extends AbstractCommand
{
    /**
     * Generate session
     *
     * @return void
     */
    public function generate(): void
    {
        $create_at = date("YmdHis");
        $filename = sprintf("Version%s%sTable", $create_at, ucfirst(Str::camel('caches')));

        $generator = new Generator(
            $this->setting->getMigrationDirectory(),
            $filename
        );

        $generator->write('model/cache', [
            'className' => $filename
        ]);

        echo Color::green('Cache migration created.');
    }
}
