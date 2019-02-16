<?php

namespace Bow\Console\Command;

class ClearCommand extends AbstractCommand
{
    /**
     * Clear cache
     *
     * @param string $target
     *
     * @return void
     */
    public function clear($target)
    {
        if (in_array($target, ['view', 'cache', 'session', 'log', 'all'])) {
            $this->throwFailsCommand('', 'clear help');
        }

        if ($target == 'all') {
            $this->unlinks($this->setting->getVarDirectory().'/cache/bow');
            $this->unlinks($this->setting->getVarDirectory().'/cache/view');
            $this->unlinks($this->setting->getVarDirectory().'/cache/session');
            $this->unlinks($this->setting->getVarDirectory().'/cache/logs');

            return;
        }

        if ($target == 'view') {
            $this->unlinks($this->setting->getVarDirectory().'/cache/view');

            return;
        }

        if ($target == 'cache') {
            $this->unlinks($this->setting->getVarDirectory().'/cache/cache');

            return;
        }

        if ($target == 'session') {
            $this->unlinks($this->setting->getVarDirectory().'/cache/session');

            return;
        }

        if ($target == 'log') {
            $this->unlinks($this->setting->getVarDirectory().'/cache/logs');

            return;
        }
    }
}
