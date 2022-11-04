<?php

namespace Bow\Tests\Config;

use Bow\Configuration\Loader as ConfigurationLoader;

class TestingKernel extends ConfigurationLoader
{
    public function namespaces(): array
    {
        return [];
    }

    public function middlewares(): array
    {
        return [];
    }

    public function events(): array
    {
        return [];
    }
}
