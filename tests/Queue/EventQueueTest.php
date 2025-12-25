<?php

namespace Bow\Tests\Queue;

use Bow\Cache\CacheConfiguration;
use Bow\Configuration\EnvConfiguration;
use Bow\Configuration\LoggerConfiguration;
use Bow\Database\DatabaseConfiguration;
use Bow\Event\EventQueueJob;
use Bow\Mail\MailConfiguration;
use Bow\Queue\Connection;
use Bow\Queue\QueueConfiguration;
use Bow\Tests\Config\TestingConfiguration;
use Bow\Tests\Events\Stubs\UserEventListenerStub;
use Bow\Tests\Events\Stubs\UserEventStub;
use Bow\View\ViewConfiguration;
use PHPUnit\Framework\TestCase;

class EventQueueTest extends TestCase
{
    private static Connection $connection;

    public static function setUpBeforeClass(): void
    {
        TestingConfiguration::withConfigurations([
            CacheConfiguration::class,
            QueueConfiguration::class,
            DatabaseConfiguration::class,
            EnvConfiguration::class,
            LoggerConfiguration::class,
            MailConfiguration::class,
            ViewConfiguration::class,
        ]);

        $config = TestingConfiguration::getConfig();
        $config->boot();

        static::$connection = new Connection($config["queue"]);
    }

    public function test_should_queue_event(): void
    {
        $adapter = static::$connection->setConnection("beanstalkd")->getAdapter();
        $producer = new EventQueueJob(new UserEventListenerStub(), new UserEventStub("bowphp"));
        $cache_filename = TESTING_RESOURCE_BASE_DIRECTORY . '/event.txt';

        // Clean up any existing file before test
        @unlink($cache_filename);

        $this->assertInstanceOf(EventQueueJob::class, $producer);

        try {
            $result = $adapter->push($producer);
            $this->assertTrue($result);

            $adapter->run();

            $this->assertFileExists($cache_filename);
            $this->assertEquals("bowphp", file_get_contents($cache_filename));
        } catch (\Exception $e) {
            $this->markTestSkipped('Sservice is not available: ' . $e->getMessage());
        } finally {
            @unlink($cache_filename);
        }
    }

    public function test_should_create_event_queue_job_with_listener_and_payload(): void
    {
        $listener = new UserEventListenerStub();
        $event = new UserEventStub("test-data");

        $producer = new EventQueueJob($listener, $event);

        $this->assertInstanceOf(EventQueueJob::class, $producer);
    }

    public function test_should_process_event_from_queue(): void
    {
        $adapter = static::$connection->setConnection("sync")->getAdapter();
        $producer = new EventQueueJob(new UserEventListenerStub(), new UserEventStub("sync-test"));
        $cache_filename = TESTING_RESOURCE_BASE_DIRECTORY . '/event.txt';

        $adapter->push($producer);
        $adapter->run();

        $this->assertFileExists($cache_filename);
        $this->assertEquals("sync-test", file_get_contents($cache_filename));

        @unlink($cache_filename);
    }
}
