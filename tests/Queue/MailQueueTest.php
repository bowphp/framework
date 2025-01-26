<?php

use Bow\Cache\CacheConfiguration;
use Bow\Configuration\EnvConfiguration;
use Bow\Configuration\LoggerConfiguration;
use Bow\Mail\Envelop;
use Bow\Mail\MailConfiguration;
use Bow\Mail\MailQueueProducer;
use Bow\Queue\Connection as QueueConnection;
use Bow\Queue\QueueConfiguration;
use Bow\Tests\Config\TestingConfiguration;
use Bow\View\ViewConfiguration;

class MailQueueTest extends PHPUnit\Framework\TestCase
{
    private static $connection;

    public static function setUpBeforeClass(): void
    {
        TestingConfiguration::withConfigurations([
            CacheConfiguration::class,
            QueueConfiguration::class,
            EnvConfiguration::class,
            LoggerConfiguration::class,
            MailConfiguration::class,
            ViewConfiguration::class,
        ]);
        $config = TestingConfiguration::getConfig();
        $config->boot();

        static::$connection = new QueueConnection($config["queue"]);
    }

    public function testQueueMail()
    {
        $envelop = new Envelop();
        $envelop->to("bow@bow.org");
        $envelop->subject("hello from bow");
        $producer = new MailQueueProducer("email", [], $envelop);

        $adapter = static::$connection->setConnection("beanstalkd")->getAdapter();

        $adapter->push($producer);

        $adapter->run();
    }
}
