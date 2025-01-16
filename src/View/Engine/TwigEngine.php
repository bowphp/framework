<?php

declare(strict_types=1);

namespace Bow\View\Engine;

use Bow\Application\Exception\ApplicationException;
use Bow\Configuration\Loader as ConfigurationLoader;
use Bow\View\EngineAbstract;

class TwigEngine extends EngineAbstract
{
    /**
     * The template engine instance
     *
     * @var \Twig\Environment
     */
    private \Twig\Environment $template;

    /**
     * The engine name
     *
     * @var string
     */
    protected string $name = 'twig';

    /**
     * TwigEngine constructor.
     *
     * @param array $config
     * @return \Twig\Environment
     * @throws ApplicationException
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        $loader = new \Twig\Loader\FilesystemLoader($config['path']);

        $additional_options = $config['additional_options'] ?? [];

        $env = [
            'auto_reload' => true,
            'debug' => true,
            'cache' => $config['cache']
        ];

        if (is_array($additional_options)) {
            foreach ($additional_options as $key => $additional_option) {
                $env[$key] = $additional_option;
            }
        }

        $this->template = new \Twig\Environment($loader, $env);

        // Add variable in global scope in the Twig use case
        $configuration_loader = ConfigurationLoader::getInstance();
        $this->template->addGlobal('_public', $configuration_loader['app.static']);
        $this->template->addGlobal('_root', $configuration_loader['app.root']);

        // Add function in global scope in Twig use case
        foreach (EngineAbstract::HELPERS as $helper) {
            $this->template->addFunction(
                new \Twig\TwigFunction($helper, $helper)
            );
        }
    }

    /**
     * @inheritDoc
     */
    public function render($filename, array $data = []): string
    {
        $filename = $this->checkParseFile($filename);

        return $this->template->render($filename, $data);
    }

    /**
     * The get engine instance
     *
     * @return \Twig\Environment
     */
    public function getEngine(): \Twig\Environment
    {
        return $this->template;
    }
}
