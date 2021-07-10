<?php

namespace Bow\Console;

use Bow\Console\Color;

trait ConsoleInformation
{
    /**
     * Throw fails command
     *
     * @param string $message
     * @param string $command
     * @throws \ErrorException
     */
    protected function throwFailsCommand($message, $command = null)
    {
        echo Color::red($message)."\n";

        if (!is_null($command)) {
            echo Color::green(sprintf('Type "php bow %s" for more information', $command));
        }

        exit(1);
    }
}
