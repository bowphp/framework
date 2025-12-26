<?php

declare(strict_types=1);

namespace Bow\View;

use Bow\Configuration\Configuration;
use Bow\Configuration\Loader;

class ViewConfiguration extends Configuration
{
    /**
     * @inheritdoc
     */
    public function create(Loader $config): void
    {
        $this->container->bind('view', function () use ($config) {
            View::configure($config["view"]);

            return View::getInstance();
        });
    }

    /**
     * @inheritdoc
     */
    public function run(): void
    {
        $this->container->make('view');
    }
}
