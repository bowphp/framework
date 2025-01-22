<?php

declare(strict_types=1);

namespace Bow\View\Engine;

use Bow\View\EngineAbstract;
use RuntimeException;

class PHPEngine extends EngineAbstract
{
    /**
     * The engine name
     *
     * @var string
     */
    protected string $name = 'php';

    /**
     * PHPEngine constructor.
     *
     * @param array $config
     * @return void
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * @inheritdoc
     */
    public function render(string $filename, array $data = []): string
    {
        $hash_filename = $filename;

        $filename = $this->checkParseFile($filename);

        if ($this->config['path'] !== null) {
            $filename = $this->config['path'] . '/' . $filename;
        }

        $cache_hash_filename = '_PHP_' . md5($hash_filename) . '.php';
        $cache_hash_filename = $this->config['cache'] . '/' . $cache_hash_filename;

        extract($data);

        if (file_exists($cache_hash_filename)) {
            if (filemtime($cache_hash_filename) >= fileatime($filename)) {
                return $this->includeFile($cache_hash_filename);
            }
        }

        $content = file_get_contents($filename);

        // Save to cache
        file_put_contents(
            $cache_hash_filename,
            $content
        );

        return $this->includeFile($cache_hash_filename);
    }

    /**
     * include the execute filename
     *
     * @param string $filename
     * @return string
     */
    private function includeFile(string $filename): string
    {
        ob_start();

        require $filename;

        return ob_get_clean();
    }

    /**
     * @inheritDoc
     */
    public function getEngine(): mixed
    {
        throw new RuntimeException("This method cannot work for PHP native engine");
    }
}
