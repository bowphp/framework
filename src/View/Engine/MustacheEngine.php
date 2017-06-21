<?php
namespace Bow\View\Engine;

use Bow\Support\Str;
use Bow\Security\Sanitize;
use Bow\Security\Tokenize;
use Bow\View\EngineAbstract;
use Bow\Translate\Translator;
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

        $helpers = [
            'secure' => 'secure',
            'sanitaze' => 'sanitaze',
            'slugify' => 'sanitaze',
            'session' => 'session',
            'form' => 'form',
            'csrf_token' => 'csrf_token',
            'csrf_field' => 'csrf_field',
            'trans' => 'trans',
            '_public', $config['app.static'],
            '_root', $config['app.root'],
            'escape' => 'e',
            'e' => 'e',
        ];

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