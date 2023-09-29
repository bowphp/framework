<?php

use Bow\Cache\CacheConfiguration;
use Bow\Configuration\EnvConfiguration;
use Bow\Configuration\LoggerConfiguration;
use Bow\Mail\MailConfiguration;
use Bow\Mail\MailQueueProducer;
use Bow\Mail\Message;
use Bow\Queue\QueueConfiguration;
use Bow\Tests\Config\TestingConfiguration;
use Bow\Queue\Connection as QueueConnection;
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
        $message = new Message();
        $message->to("bow@bow.org");
        $message->subject("hello from bow");
        $producer = new MailQueueProducer("email", [], $message);

        $adapter = static::$connection->setConnection("beanstalkd")->getAdapter();

        $adapter->push($producer);
        $adapter->run();
    }
}
