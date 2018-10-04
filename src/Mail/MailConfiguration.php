<?php

namespace Bow\Mail;

use Bow\Configuration\Configuration;
use Bow\Configuration\Loader;

class MailConfiguration extends Configuration
{
    /**
     * @inheritdoc
     */
    public function create(Loader $config)
    {
        $this->container->bind('mail', function () use ($config) {
            return Mail::configure($config['mail']);
        });
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->container->make('mail');
    }
}
