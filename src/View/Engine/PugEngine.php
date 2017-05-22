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

        $pug_config = [
            'basedir' => $config->getViewpath(),
            'prettyprint' => true,
            'extension' => $config->getTemplateExtension()
        ];

        if ($this->isCachable()) {
            $pug_config['cache'] = $config->getCachepath().'/view';
        }

        $this->template = new Pug($pug_config);
    }

    /**
     * @inheritDoc
     */
    public function render($filename, array $data = [])
    {
        $filename = $this->checkParseFile($filename);

        return $this->template->render(
            $this->config->getViewpath().'/'.$filename,
            $data
        );
    }
}