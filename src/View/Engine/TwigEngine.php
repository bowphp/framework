<?php

namespace Bow\View\Engine;

use Bow\Configuration\Loader;
use Bow\View\EngineAbstract;

class TwigEngine extends EngineAbstract
{
    /**
     * The template engine instance
     *
     * @var \Twig_Loader_Filesystem
     */
    private $template;

    /**
     * The engine name
     *
     * @var string
     */
    protected $name = 'twig';

    /**
     * TwigEngine constructor.
     *
     * @param Loader $config
     *
     * @return void
     */
    public function __construct(Loader $config)
    {
        $this->config = $config;

        $loader = new \Twig_Loader_Filesystem($config['view.path']);

        $aditionnals = $config['view.aditionnal_options'];

        $env = [
            'auto_reload' => true,
            'debug' => true,
            'cache' => $config['view.cache']
        ];

        if (is_array($aditionnals)) {
            foreach ($aditionnals as $key => $aditionnal) {
                $env[$key] = $aditionnal;
            }
        }

        $this->template = new \Twig_Environment($loader, $env);

        // Add variable in global scope in the Twig use case
        $this->template->addGlobal('_public', $config['app.static']);

        $this->template->addGlobal('_root', $config['app.root']);

        // Add function in global scope in Twig use case
        foreach (EngineAbstract::HELPERS as $helper) {
            $this->template->addFunction(
                new \Twig_SimpleFunction($helper, $helper)
            );
        }

        return $this->template;
    }

    /**
     * {@inheritdoc}
     */
    public function render($filename, array $data = [])
    {
        $filename = $this->checkParseFile($filename);

        return $this->template->render($filename, $data);
    }

    /**
     * The get engine instance
     *
     * @return \Twig_Environment|\Twig_Loader_Filesystem
     */
    public function getTemplate()
    {
        return $this->template;
    }
}
