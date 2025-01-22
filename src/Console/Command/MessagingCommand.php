<?php

declare(strict_types=1);

namespace Bow\Console\Command;

use Bow\Console\AbstractCommand;
use Bow\Console\Color;
use Bow\Console\Generator;
use JetBrains\PhpStorm\NoReturn;

class MessagingCommand extends AbstractCommand
{
    /**
     * Generate session
     *
     * @param string $messaging
     * @return void
     */
    #[NoReturn] public function generate(string $messaging): void
    {
        $generator = new Generator(
            $this->setting->getMessagingDirectory(),
            $messaging
        );

        if ($generator->fileExists()) {
            echo Color::red("The messaging already exists");

            exit(1);
        }

        $generator->write('messaging', [
            'baseNamespace' => $this->namespaces['messaging'] ?? "App\\Messaging",
        ]);

        echo Color::green("The messaging has been well created.");

        exit(0);
    }
}
