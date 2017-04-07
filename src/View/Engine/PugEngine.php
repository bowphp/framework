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
    private $templte;

    /**
     * PugEngine constructor.
     * @param Configuration $config
     */
    public function __construct(Configuration $config)
    {
        $this->config = $config;
        $this->templte = new Pug([
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
        return $this->templte->render(file_get_contents($filename), $data);
    }
}