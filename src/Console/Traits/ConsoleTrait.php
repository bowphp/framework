<?php

namespace Bow\Console\Traits;

use Bow\Console\Color;

trait ConsoleTrait
{
    /**
     * Throw fails command
     *
     * @param string $message
     * @param string $command
     * @throws \ErrorException
     */
    protected function throwFailsCommand(string $message, ?string $command = null)
    {
        echo Color::red($message)."\n";

        if (!is_null($command)) {
            echo Color::green(sprintf('Type "php bow %s" for more information', $command));
        }

        exit(1);
    }
}
