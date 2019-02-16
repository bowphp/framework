<?php

namespace Bow\Console\Command;

class GenerateKeyCommand extends AbstractCommand
{
    /**
     * Generate Key
     *
     * @return void
     */
    public function generate()
    {
        $key = base64_encode(openssl_random_pseudo_bytes(12).date('Y-m-d H:i:s').microtime(true));

        file_put_contents($this->setting->getConfigDirectory()."/.key", $key);

        echo "Application key => \033[0;32m$key\033[00m\n";

        exit;
    }
}
