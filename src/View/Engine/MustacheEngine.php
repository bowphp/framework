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

        $helpers = [
            'secure' => 'secure',
            'route' => 'route',
            'bow_hash' => 'bow_hash',
            'config' => 'config',
            'faker' => 'faker',
            'env' => 'env',
            'app_mode' => 'app_mode',
            'app_lang' => 'app_lang',
            'flash' => 'flash',
            'cache' => 'cache',
            'encrypt' => 'encrypt',
            'decrypt' => 'decrypt',
            'collect' => 'collect',
            'url' => 'url',
            'get_header' => 'get_header',
            'input' => 'input',
            'response' => 'response',
            'request' => 'request',
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