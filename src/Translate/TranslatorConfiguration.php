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
            $auto_detected = $config['translate.auto_detected'] ?? false;
            $lang = $config['translate.lang'];
            $dictionary = $config['translate.dictionary'];

            if ($auto_detected) {
                $lang = app("request")->lang();
                if (is_string($lang)) {
                    $lang = strtolower($lang);
                } else {
                    $lang = $config['translate.lang'];
                }
            }

            return Translator::configure($lang, $dictionary, $auto_detected);
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
