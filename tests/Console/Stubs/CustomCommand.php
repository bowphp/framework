<?php

namespace Bow\Tests\Console\Stubs;

use Bow\Console\Command\AbstractCommand as ConsoleCommand;

class CustomCommand extends ConsoleCommand
{
    public function process()
    {
        file_put_contents(TESTING_RESOURCE_BASE_DIRECTORY . '/test_custom_command.txt', 'ok');
    }
}
