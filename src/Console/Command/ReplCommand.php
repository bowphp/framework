<?php


class ReplCommand extends AbstratCommand
{
    /**
     * Launch the REPL console
     *
     * @return void
     */
    public function console()
    {
        if (is_string($this->arg->getParameter('--include'))) {
            $this->setBootstrap(
                array_merge($this->bootstrap, [$this->arg->getParameter('--include')])
            );
        }

        if (!class_exists('\Psy\Shell')) {
            echo 'Please, insall psy/psysh:@stable';

            return;
        }

        $config = new \Psy\Configuration();

        $config->setPrompt('(bow) >> ');

        $config->setUpdateCheck(\Psy\VersionUpdater\Checker::NEVER);

        $shell = new \Psy\Shell($config);

        $shell->setIncludes($this->bootstrap);

        $shell->run();
    }
}
