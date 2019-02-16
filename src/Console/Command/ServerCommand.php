<?php

namespace Bow\Console\Command;

use Bow\Console\Setting;

class ServerCommand extends AbstractCommand
{
    /**
     * The run server command
     *
     * @return void
     */
    public function run()
    {
        $port = (int) $this->arg->options('--port', 5000);

        $hostname = $this->arg->options('--host', 'localhost');

        $settings = $this->arg->options('--php-settings', false);

        if (is_bool($settings)) {
            $settings = '';
        } else {
            $settings = '-d '.$settings;
        }

        // resource.
        $writing_stream = fopen("php://stdout", "w");

        $message = sprintf(
            "[%s] Server start at http://%s:%s \033[0;31;7mCTRL-C for shutdown it\033[00m\n",
            $hostname,
            date('F d Y H:i:s a'),
            $port
        );

        fwrite($writing_stream, $message);

        // Close Open stream
        fclose($writing_stream);

        $filename = $this->setting->getServerFilename();
        $public_directory = $this->setting->getPublicDirectory();

        // Launch the dev server.
        shell_exec(
            "php -S $hostname:$port -t {$public_directory} ".$filename." $settings"
        );
    }
}
