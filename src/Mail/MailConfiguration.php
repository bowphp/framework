<?php

declare(strict_types=1);

namespace Bow\Mail;

use Bow\Configuration\Configuration;
use Bow\Configuration\Loader;

class MailConfiguration extends Configuration
{
    /**
     * @inheritdoc
     */
    public function create(Loader $config): void
    {
        $this->container->bind('mail', function () use ($config) {
            return Mail::configure($config['mail']);
        });
    }

    /**
     * @inheritdoc
     */
    public function run(): void
    {
        $this->container->make('mail');
    }
}
