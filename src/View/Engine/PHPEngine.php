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

        if ($this->config->getViewpath() !== null) {
            $filename = $this->config->getViewpath() . '/' . $filename;
        }

        $cache_hash_filename = '_PHP_'.hash('sha1', $hash_filename).'.php';
        $cache_hash_filename = $this->config->getCachepath().'/view/'.$cache_hash_filename;

        extract($data);
        ob_start();

        if ($this->isCachable() && file_exists($cache_hash_filename)) {
            if (filemtime($cache_hash_filename) >= fileatime($filename)) {
                require $cache_hash_filename;
                return ob_get_clean();
            }
        }

        require $filename;
        $data = ob_get_clean();

        if ($this->isCachable()) {
            // Mise en cache
            file_put_contents(
                $cache_hash_filename,
                file_get_contents($filename)
            );
        }

        return $data;
    }
}