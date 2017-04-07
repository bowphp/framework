<?php
namespace Bow\View\Engine;

use Bow\Support\Str;
use Bow\Security\Security;
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
        $this->template = new \Mustache_Engine([
            'cache' => $config->getCachepath(),
            'loader' => new \Mustache_Loader_FilesystemLoader($config->getViewpath()),
            'helpers' => [
                'secure' => function($data) {
                    return Security::sanitaze($data, true);
                },
                'sanitaze' => function($data) {
                    return Security::sanitaze($data);
                },
                'slugify' => function($data) {
                    return Str::slugify($data);
                },
                'csrf_token' => function() {
                    return Security::getCsrfToken()->token;
                },
                'csrf_field' => function() {
                    return Security::getCsrfToken()->field;
                },
                'public', $config->getPublicPath(),
                'root', $config->getApproot()
            ]
        ]);
    }

    /**
     * @inheritDoc
     */
    public function render($filename, array $data = [])
    {
        $filename = $this->checkParseFile($filename);
        $this->template->render(file_get_contents($filename), $data);
    }
}