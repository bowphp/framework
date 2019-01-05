<?php

namespace Bow\View\Engine;

use Bow\Configuration\Loader;
use Bow\View\EngineAbstract;

class PHPEngine extends EngineAbstract
{
    /**
     * @var string
     */
    protected $name = 'php';

    /**
     * PHPEngine constructor.
     *
     * @param Loader $config
     */
    public function __construct(Loader $config)
    {
        $this->config = $config;
    }

    /**
     * @inheritDoc
     * @throws
     */
    public function render($filename, array $data = [])
    {
        $hash_filename = $filename;

        $filename = $this->checkParseFile($filename);

        if ($this->config['view.path'] !== null) {
            $filename = $this->config['view.path'] . '/' . $filename;
        }

        $cache_hash_filename = '_PHP_'.md5($hash_filename).'.php';

        $cache_hash_filename = $this->config['view.cache'].'/'.$cache_hash_filename;

        extract($data);

        if (file_exists($cache_hash_filename)) {
            if (filemtime($cache_hash_filename) >= fileatime($filename)) {
                ob_start();

                require $cache_hash_filename;

                return ob_get_clean();
            }
        }

        ob_start();

        $content = file_get_contents($filename);

        // Mise en cache
        file_put_contents(
            $cache_hash_filename,
            $content
        );

        require $cache_hash_filename;

        return ob_get_clean();
    }
}
