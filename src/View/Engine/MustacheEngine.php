<?php
namespace Bow\View\Engine;

use Bow\View\EngineAbstract;
use Bow\Application\Configuration;

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
     * MustacheEngine constructor.
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;

        $partial_loader = is_dir($config['view.path'].'/partials') ?
            new \Mustache_Loader_FilesystemLoader($config['view.path'].'/partials', [
                'extension' => $this->config['view.extension']
            ]) : null;

        $loader = new \Mustache_Loader_FilesystemLoader($config['view.path'], [
            'extension' => $this->config['view.extension']
        ]);

        $helpers = array_merge(
            [ '_public', $config['app.static'], '_root', $config['app.root']],
            EngineAbstract::HELPERS
        );

        $this->template = new \Mustache_Engine([
            'cache' => $config['view.cache'],
            'loader' => $loader,
            'partials_loader' => $partial_loader,
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