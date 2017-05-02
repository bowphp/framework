<?php
namespace Bow\View\Engine;


use Bow\Application\Configuration;
use Bow\View\EngineAbstract;
use Pug\Pug;

class PugEngine extends EngineAbstract
{
    /**
     * @var string
     */
    protected $name = 'pug';

    /**
     * @var Pug
     */
    private $template;

    /**
     * PugEngine constructor.
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
        $this->template = new Pug([
            'basedir' => $config->getViewpath(),
            'cache' => $config->getCachepath(),
            'prettyprint' => true,
            'extension' => $config->getTemplateExtension()
        ]);
    }

    /**
     * @inheritDoc
     */
    public function render($filename, array $data = [])
    {
        $filename = $this->checkParseFile($filename);
        return $this->template->render($this->config->getViewpath().'/'.$filename, $data);
    }
}