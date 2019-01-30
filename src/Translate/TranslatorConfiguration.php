<?php

namespace Bow\Translate;

use Bow\Configuration\Configuration;
use Bow\Configuration\Loader;

class TranslatorConfiguration extends Configuration
{
    /**
     * @inheritdoc
     */
    public function create(Loader $config)
    {
        $this->container->bind('trans', function () use ($config) {
            $auto_detected = is_null($config['trans.auto_detected'])
                ? false
                : $config['trans.auto_detected'];

            return Translator::configure(
                $config['trans.lang'],
                $config['trans.dictionary'],
                $auto_detected
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
