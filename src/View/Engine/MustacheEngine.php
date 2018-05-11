<?php
namespace Bow\View\Engine;

use Bow\View\EngineAbstract;
use Bow\Config\Config;

class MustacheEngine extends EngineAbstract
{
    /**
     * @var string
     */
    protected $name = 'mustache';

    /**
     * @var \Mustache_Engine
     */
    private $template;

    /**
     * @var \Mustache_Loader_FilesystemLoader
     */
    private $partails_loader;

    /**
     * MustacheEngine constructor.
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;

        if (is_dir($config['view.path'].'/partials')) {
            $this->partails_loader = new \Mustache_Loader_FilesystemLoader(
                $config['view.path'].'/partials', [
                'extension' => $this->config['view.extension']
            ]);
        }

        $loader = new \Mustache_Loader_FilesystemLoader($config['view.path'], [
            'extension' => $this->config['view.extension']
        ]);

        $helpers = array_merge(
            ['_public', $config['app.static'], '_root', $config['app.root']],
            EngineAbstract::HELPERS
        );

        $this->template = new \Mustache_Engine([
            'cache' => $config['view.cache'].'/view',
            'loader' => $loader,
            'partials_loader' => $this->partails_loader,
            'helpers' => $helpers
        ]);
    }

    /**
     * @inheritDoc
     */
    public function render($filename, array $data = [])
    {
        $filename = $this->checkParseFile($filename);

        return $this->template->render(
            $filename,
            $data
        );
    }
}
