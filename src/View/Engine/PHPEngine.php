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
                return include $cache_hash_filename;
            }
        }

        $content[] = '<?php ob_start(); ?>';
        $content[] = trim(file_get_contents($filename));
        $content[] = '<?php $__bow_php_rendering_content = ob_get_clean(); ?>';
        $content[] = '<?php return $__bow_php_rendering_content; ?>';

        // Mise en cache
        file_put_contents(
            $cache_hash_filename,
            implode("\n", $content)
        );

        return include $filename;
    }
}
