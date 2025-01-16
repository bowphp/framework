<?php

declare(strict_types=1);

namespace Bow\Console\Traits;

use Bow\Console\Color;
use JetBrains\PhpStorm\NoReturn;

trait ConsoleTrait
{
    /**
     * Throw fails command
     *
     * @param string $message
     * @param string|null $command
     * @return void
     */
    #[NoReturn] protected function throwFailsCommand(string $message, ?string $command = null): void
    {
        echo Color::red($message) . "\n";

        if (!is_null($command)) {
            echo Color::green(sprintf('Type "php bow %s" for more information', $command));
        }

        exit(1);
    }
}
