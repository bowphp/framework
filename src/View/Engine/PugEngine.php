<?php

namespace Bow\View\Engine;

use Bow\Configuration\Loader;
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
     * @param Loader $config
     */
    public function __construct(Loader $config)
    {
        $this->config = $config;

        $option = [
            'basedir' => $config['view.path'],
            'prettyprint' => true,
            'extension' => $config['view.extension'],
            'cache' => $config['view.cache']
        ];

        $aditionnals = $config['view.aditionnal_options'];

        if (is_array($aditionnals)) {
            foreach ($aditionnals as $key => $aditionnal) {
                $option[$key] = $aditionnal;
            }
        }

        $this->template = new Pug($option);

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
