<?php
namespace Bow\View\Engine;

use Bow\Application\Configuration;
use Bow\View\EngineAbstract;

class PHPEngine extends EngineAbstract
{
    /**
     * @var string
     */
    protected $name = 'php';

    /**
     * PHPEngine constructor.
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function render($filename, array $data = [])
    {
        $hash_filename = $filename;
        $filename = $this->checkParseFile($filename);

        if ($this->config['view.path'] !== null) {
            $filename = $this->config['view.path'] . '/' . $filename;
        }

        $cache_hash_filename = '_PHP_'.hash('sha1', $hash_filename).'.php';
        $cache_hash_filename = $this->config['view.cache'].'/view/'.$cache_hash_filename;

        extract($data);

        if (file_exists($cache_hash_filename)) {
            if (filemtime($cache_hash_filename) >= fileatime($filename)) {
                 return require $cache_hash_filename;
            }
        }

        ob_start();
         require $filename;
        $data = ob_get_clean();

        $content = file_get_contents($filename);
        // Mise en cache
        file_put_contents(
            $cache_hash_filename,
            <<<PHP
<?php ob_start(); ?>$content<?php \$__bow_php_rendering_content = ob_get_clean(); ?>
<?php 
return \$__bow_php_rendering_content;
PHP
        );

        return $data;
    }
}