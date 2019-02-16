<?php

namespace Bow\Console\Command;

use Bow\Console\Generator;

class ConfigurationCommand extends AbstractCommand
{
    /**
     * Add configuration
     *
     * @param string $configuration
     *
     * @return void
     */
    public function generate($configuration)
    {
        $generator = new Generator(
            $this->setting->getPackageDirectory(),
            $configuration
        );

        if ($generator->fileExists()) {
            echo "\033[0;33mThe configuration already exists.\033[00m\n";

            exit 0;
        }

        $generator->write('configuration', [
            'baseNamespace' => $this->namespaces['configuration']
        ]);

        echo "\033[0;32mThe configuration was well created.\033[00m\n";

        exit 0;
    }
}
