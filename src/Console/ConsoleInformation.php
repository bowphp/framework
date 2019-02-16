<?php

namespace Bow\Console;

use Bow\Console\Color;

trait ConsoleInformation
{
    /**
     * The default error message
     *
     * @var string
     */
    private $template =
        "Please type this command \033[0;32;7m`php bow help` or `php bow command help` for more information.";

    /**
     * Throw fails command
     *
     * @param string $command
     *
     * @throws \ErrorException
     */
    private function throwFailsCommand($message, $command = null)
    {
        echo Color::red($message);

        if (!is_null($command)) {
            echo Color::red(sprintf('Type "php bow %s" for more information', $command));
        }

        exit(1);
    }

    private function printRedMessage()
    {
    }
}
