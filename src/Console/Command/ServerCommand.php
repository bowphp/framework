<?php

declare(strict_types=1);

namespace Bow\Console\Command;

use Bow\Console\AbstractCommand;

class ServerCommand extends AbstractCommand
{
    /**
     * The run server command
     *
     * @return void
     */
    public function run(): void
    {
        $port = (int)$this->arg->getParameter('--port', 5000);
        $hostname = $this->arg->getParameter('--host', 'localhost');
        $settings = $this->arg->getParameter('--php-settings', false);

        if (is_bool($settings)) {
            $settings = '';
        } else {
            $settings = '-d ' . $settings;
        }

        $filename = $this->setting->getServerFilename();
        $public_directory = $this->setting->getPublicDirectory();

        // Launch the dev server.
        shell_exec(
            "php -S $hostname:$port -t {$public_directory} " . $filename . " $settings"
        );
    }
}
