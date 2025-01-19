<?php

declare(strict_types=1);

namespace Bow\Console\Command;

use Bow\Console\AbstractCommand;
use Bow\Console\Color;
use Bow\Console\Generator;
use Bow\Support\Str;

class GenerateNotificationCommand extends AbstractCommand
{
    /**
     * Generate session
     *
     * @return void
     */
    public function generate(): void
    {
        $create_at = date("YmdHis");
        $filename = sprintf("Version%s%sTable", $create_at, ucfirst(Str::camel('notification')));

        $generator = new Generator(
            $this->setting->getMigrationDirectory(),
            $filename
        );

        $generator->write('model/notification', [
            'className' => $filename
        ]);

        echo Color::green('Notification migration created.');
    }
}
