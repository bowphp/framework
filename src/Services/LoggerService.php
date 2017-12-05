<?php

namespace Bow\Services;

use Monolog\Logger;
use Bow\Config\Config;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FirePHPHandler;
use Bow\Application\Services as BowService;

class LoggerService extends BowService
{
    /**
     * @var mixed
     */
    private $whoops;

    /**
     * @var mixed
     */
    private $monolog;

    /**
     * @var Config
     */
    private $config;

    /**
     * Permet de créer le service
     *
     * @param Config $config
     * @param Config $config
     */
    public function make(Config $config = null)
    {
        $this->whoops = new \Whoops\Run;

        $this->logger = new Logger('BOW');

        $this->config = $config;
    }

    /**
     * Permet de lancer le service
     *
     * @return mixed
     */
    public function start()
    {
        $this->whoops->pushHandler(new \Whoops\Handler\PrettyPageHandler);
        $this->whoops->register();
        
        $this->logger->pushHandler(new StreamHandler($this->config['resource.log'].'/bow.log', Logger::DEBUG));
        $this->logger->pushHandler(new FirePHPHandler());
    }
}