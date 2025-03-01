<?php

declare(strict_types=1);

namespace Bow\Console\Command;

use Bow\Console\AbstractCommand;
use Bow\Console\Color;
use Bow\Console\Generator;
use Bow\Support\Str;

class GenerateQueueCommand extends AbstractCommand
{
    /**
     * Generate session
     *
     * @return void
     */
    public function generate(): void
    {
        $create_at = date("YmdHis");
        $filename = sprintf("Version%s%sTable", $create_at, ucfirst(Str::camel('queues')));

        $generator = new Generator(
            $this->setting->getMigrationDirectory(),
            $filename
        );

        $generator->write('model/queue', [
            'className' => $filename
        ]);

        echo Color::green('Queue migration created.');
    }
}
