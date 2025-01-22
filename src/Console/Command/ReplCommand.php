<?php

declare(strict_types=1);

namespace Bow\Console\Command;

use Bow\Console\AbstractCommand;
use Bow\Console\Color;
use Psy\Configuration;
use Psy\Shell;
use Psy\VersionUpdater\Checker;

class ReplCommand extends AbstractCommand
{
    /**
     * Launch the REPL console
     *
     * @return void
     */
    public function run(): void
    {
        $include = $this->arg->getParameter('--include');

        if (is_string($include)) {
            $bootstraps = array_merge(
                $this->setting->getBootstrap(),
                (array)$include
            );

            $this->setting->setBootstrap($bootstraps);
        }

        if (!class_exists('\Psy\Shell')) {
            echo Color::red('Please, install psy/psysh:@stable');

            return;
        }

        $config = new Configuration();
        $config->setUpdateCheck(Checker::NEVER);

        // Load the custom prompt
        $prompt = $this->arg->getParameter('--prompt', '(bow) >>');
        $prompt = trim($prompt) . ' ';

        $config->theme()->setPrompt($prompt);

        $shell = new Shell($config);

        $shell->setIncludes($this->setting->getBootstrap());
        $shell->run();
    }
}
