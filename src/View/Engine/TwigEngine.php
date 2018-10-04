<?php

namespace Bow\View\Engine;

use Bow\Configuration\Loader;
use Bow\View\EngineAbstract;

class TwigEngine extends EngineAbstract
{
    /**
     * @var \Twig_Loader_Filesystem
     */
    private $template;

    /**
     * @var string
     */
    protected $name = 'twig';

    /**
     * TwigEngine constructor.
     *
     * @param Loader $config
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

        /**
         * - Ajout de variable globale
         * dans le cadre de l'utilisation de Twig
         */
        $this->template->addGlobal('_public', $config['app.static']);

        $this->template->addGlobal('_root', $config['app.root']);

        /**
         * - Ajout de fonction global
         *  dans le cadre de l'utilisation de Twig
         */
        foreach (EngineAbstract::HELPERS as $helper) {
            $this->template->addFunction(new \Twig_SimpleFunction($helper, $helper));
        }

        return $this->template;
    }

    /**
     * @inheritDoc
     * @throws
     */
    public function render($filename, array $data = [])
    {
        $filename = $this->checkParseFile($filename);

        return $this->template->render($filename, $data);
    }

    /**
     * @return \Twig_Environment|\Twig_Loader_Filesystem
     */
    public function getTemplate()
    {
        return $this->template;
    }
}
