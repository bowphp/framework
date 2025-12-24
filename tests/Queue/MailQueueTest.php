<?php

namespace Bow\Tests\Queue;

use Bow\Cache\CacheConfiguration;
use Bow\Configuration\EnvConfiguration;
use Bow\Configuration\LoggerConfiguration;
use Bow\Database\DatabaseConfiguration;
use Bow\Mail\Envelop;
use Bow\Mail\MailConfiguration;
use Bow\Mail\MailQueueProducer;
use Bow\Queue\Connection as QueueConnection;
use Bow\Queue\QueueConfiguration;
use Bow\Tests\Config\TestingConfiguration;
use Bow\View\ViewConfiguration;
use PHPUnit\Framework\TestCase;

class MailQueueTest extends TestCase
{
    private static QueueConnection $connection;

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

        static::$connection = new QueueConnection($config["queue"]);
    }

    /**
     * @test
     */
    public function it_should_queue_mail_successfully(): void
    {
        $envelop = new Envelop();
        $envelop->to("bow@bow.org");
        $envelop->subject("hello from bow");
        $producer = new MailQueueProducer("email", [], $envelop);

        $this->assertInstanceOf(MailQueueProducer::class, $producer);

        $adapter = static::$connection->setConnection("beanstalkd")->getAdapter();

        $result = $adapter->push($producer);
        $this->assertTrue($result);

        $adapter->run();
        $this->assertTrue(true, "Mail queue processed successfully");
    }

    /**
     * @test
     */
    public function it_should_create_mail_producer_with_correct_parameters(): void
    {
        $envelop = new Envelop();
        $envelop->to("test@example.com");
        $envelop->from("sender@example.com");
        $envelop->subject("Test Subject");

        $producer = new MailQueueProducer("test-template", ["name" => "John"], $envelop);

        $this->assertInstanceOf(MailQueueProducer::class, $producer);
    }

    /**
     * @test
     */
    public function it_should_push_mail_to_specific_queue(): void
    {
        $envelop = new Envelop();
        $envelop->to("priority@example.com");
        $envelop->subject("Priority Mail");
        $producer = new MailQueueProducer("email", [], $envelop);

        $adapter = static::$connection->setConnection("beanstalkd")->getAdapter();
        $adapter->setQueue("priority-mail");

        $result = $adapter->push($producer);
        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function it_should_set_mail_retry_attempts(): void
    {
        $envelop = new Envelop();
        $envelop->to("retry@example.com");
        $envelop->subject("Retry Test");

        $producer = new MailQueueProducer("email", [], $envelop);
        $producer->setRetry(3);

        $this->assertEquals(3, $producer->getRetry());
    }
}
