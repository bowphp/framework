<?php

declare(strict_types=1);

namespace Bow\Console\Command\Generator;

use Bow\Console\AbstractCommand;
use Bow\Console\Color;
use Bow\Console\Generator;

class GenerateNotifierCommand extends AbstractCommand
{
    /**
     * Generate session
     *
     * @param  string $messaging
     * @return void
     */
    public function run(string $notifier): void
    {
        $generator = new Generator(
            $this->setting->getNotifierDirectory(),
            $notifier
        );

        if ($generator->fileExists()) {
            echo Color::red("The notifier already exists");

            exit(1);
        }

        $generator->write('notifier', [
            'baseNamespace' => $this->namespaces['notifier'] ?? "App\\Notifier",
        ]);

        echo Color::green("The notifier {$this->setting->getNotifierDirectory()}/{$notifier} has been well created.");
        exit(0);
    }
}
