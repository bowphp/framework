<?php

namespace Bow\Console\Command;

use Bow\Console\Color;

class ReplCommand extends AbstractCommand
{
    /**
     * Launch the REPL console
     *
     * @return mixed
     */
    public function run(): void
    {
        $include = $this->arg->getParameter('--include');

        if (is_string($include)) {
            $bootstraps = array_merge(
                $this->setting->getBootstrap(),
                (array) $include
            );

            $this->setting->setBootstrap($bootstraps);
        }

        if (!class_exists('\Psy\Shell')) {
            echo Color::red('Please, insall psy/psysh:@stable');

            return;
        }

        $config = new \Psy\Configuration();

        $config->setUpdateCheck(\Psy\VersionUpdater\Checker::NEVER);

        // Load the custum prompt
        $prompt = $this->arg->getParameter('--prompt');

        if (is_null($prompt)) {
            $prompt = '(bow) >>';
        }

        $prompt = trim($prompt).' ';
        
        $config->setPrompt($prompt);

        $shell = new \Psy\Shell($config);

        $shell->setIncludes($this->setting->getBootstrap());
        $shell->run();
    }
}
