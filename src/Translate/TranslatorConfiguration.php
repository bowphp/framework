<?php

namespace Bow\Translate;

use Bow\Configuration\Loader;
use Bow\Configuration\Configuration;

class TranslatorConfiguration extends Configuration
{
    /**
     * @inheritdoc
     */
    public function create(Loader $config)
    {
        $this->container->bind('trans', function () use ($config) {
            return Translator::configure(
                $config['trans.lang'],
                $config['trans.directory']
            );
        });
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        $this->container->make('trans');
    }
}
