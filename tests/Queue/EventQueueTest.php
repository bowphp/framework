<?php

use Bow\Cache\CacheConfiguration;
use Bow\Configuration\EnvConfiguration;
use Bow\Configuration\LoggerConfiguration;
use Bow\Event\EventProducer;
use Bow\Mail\MailConfiguration;
use Bow\Queue\Connection;
use Bow\Queue\QueueConfiguration;
use Bow\Tests\Config\TestingConfiguration;
use Bow\Tests\Events\Stubs\UserEventListenerStub;
use Bow\Tests\Events\Stubs\UserEventStub;
use Bow\View\ViewConfiguration;

class EventQueueTest extends \PHPUnit\Framework\TestCase
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

        static::$connection = new Connection($config["queue"]);
    }

    /**
     * @test
     */
    public function it_should_queue_event()
    {
        $adapter = static::$connection->setConnection("beanstalkd")->getAdapter();
        $producer = new EventProducer(new UserEventListenerStub(), new UserEventStub("bowphp"));
        $cache_filename = TESTING_RESOURCE_BASE_DIRECTORY . '/event.txt';

        $adapter->push($producer);
        $adapter->run();

        $this->assertEquals("bowphp", file_get_contents($cache_filename));
    }
}
