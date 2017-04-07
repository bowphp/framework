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
     * @var Configuration
     */
    private $config;

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
        $filename = $this->checkParseFile($filename);

        if ($this->config->getViewpath() !== null) {
            $filename = $this->config->getViewpath() . '/' . $filename;
        }

        ob_start();

        require $filename;

        return ob_get_clean();
    }
}