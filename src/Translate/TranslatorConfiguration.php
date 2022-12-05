<?php

declare(strict_types=1);

namespace Bow\Translate;

use Bow\Configuration\Configuration;
use Bow\Configuration\Loader;

class TranslatorConfiguration extends Configuration
{
    /**
     * @inheritdoc
     */
    public function create(Loader $config): void
    {
        $this->container->bind('translate', function () use ($config) {
            $auto_detected = is_null($config['translate.auto_detected'])
                ? false
                : $config['translate.auto_detected'];

            return Translator::configure(
                $config['translate.lang'],
                $config['translate.dictionary'],
                $auto_detected
            );
        });
    }

    /**
     * @inheritdoc
     */
    public function run(): void
    {
        $this->container->make('translate');
    }
}
