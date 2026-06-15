<?php

namespace Bow\Tests\Console\Stubs;

use Bow\Console\AbstractCommand as ConsoleCommand;

class CustomCommand extends ConsoleCommand
{
    public function process()
    {
        $directory = TESTING_RESOURCE_BASE_DIRECTORY;
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        file_put_contents($directory . '/test_custom_command.txt', 'ok');
    }
}
