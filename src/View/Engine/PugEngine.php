<?php
namespace Bow\View\Engine;

use Bow\Config\Config;
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
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;

        $pug_config = [
            'basedir' => $config['view.path'],
            'prettyprint' => true,
            'extension' => $config['view.extension'],
            'cache' => $config['view.cache']
        ];

        $this->template = new Pug(
            $pug_config,
            ['expressionLanguage' => 'php']
        );

        foreach (EngineAbstract::HELPERS as $helper) {
            $this->template->share($helper, $helper);
        }
    }

    /**
     * @inheritDoc
     * @throws
     */
    public function render($filename, array $data = [])
    {
        $filename = $this->checkParseFile($filename);

        return $this->template->render(
            $this->config['view.path'].'/'.$filename,
            $data
        );
    }
}
