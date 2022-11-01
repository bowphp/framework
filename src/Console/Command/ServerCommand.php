<?php

namespace Bow\Console\Command;

class ServerCommand extends AbstractCommand
{
    /**
     * The run server command
     *
     * @return void
     */
    public function run(): void
    {
        $port = (int) $this->arg->getParameter('--port', 5000);
        $hostname = $this->arg->getParameter('--host', 'localhost');
        $settings = $this->arg->getParameter('--php-settings', false);

        if (is_bool($settings)) {
            $settings = '';
        } else {
            $settings = '-d '.$settings;
        }

        // resource.
        $writing_stream = fopen("php://stdout", "w");

        $message = sprintf(
            "[%s] Server start at http://%s:%s \033[0;31;7mCTRL-C for shutdown it\033[00m\n",
            date('F d Y H:i:s a'),
            $hostname,
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
