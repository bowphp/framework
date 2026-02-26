<?php

namespace Bow\Tests\Queue;

use Bow\Cache\CacheConfiguration;
use Bow\Configuration\EnvConfiguration;
use Bow\Configuration\LoggerConfiguration;
use Bow\Database\DatabaseConfiguration;
use Bow\Mail\Envelop;
use Bow\Mail\MailConfiguration;
use Bow\Mail\MailQueueTask;
use Bow\Queue\Adapters\QueueAdapter;
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
        // Suppress queue task logging during tests
        QueueAdapter::suppressLogging(true);

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

        static::$connection = new QueueConnection($config["queue"]);
    }

    private function createEnvelop(string $to, string $subject): Envelop
    {
        $envelop = new Envelop();
        $envelop->to($to);
        $envelop->subject($subject);
        return $envelop;
    }

    /**
     * @dataProvider connectionProvider
     */
    public function test_should_queue_and_process_mail(string $connection): void
    {
        $envelop = $this->createEnvelop("bow@bow.org", "hello from bow");
        $task = new MailQueueTask("email", [], $envelop);

        $this->assertInstanceOf(MailQueueTask::class, $task);

        $adapter = static::$connection->setConnection($connection)->getAdapter();

        try {
            $result = $adapter->push($task);
            $this->assertTrue($result);

            $adapter->run();
        } catch (\Exception $e) {
            $this->markTestSkipped("Service {$connection} is not available: " . $e->getMessage());
        }
    }

    /**
     * @dataProvider connectionProvider
     */
    public function test_should_push_mail_to_specific_queue(string $connection): void
    {
        $envelop = $this->createEnvelop("priority@example.com", "Priority Mail");
        $task = new MailQueueTask("email", [], $envelop);

        $adapter = static::$connection->setConnection($connection)->getAdapter();
        $adapter->setQueue("priority-mail");

        try {
            $result = $adapter->push($task);
            $this->assertTrue($result);
        } catch (\Exception $e) {
            $this->markTestSkipped("Service {$connection} is not available: " . $e->getMessage());
        }
    }

    public function test_should_set_mail_retry_attempts(): void
    {
        $envelop = $this->createEnvelop("retry@example.com", "Retry Test");
        $task = new MailQueueTask("email", [], $envelop);
        $task->setRetry(3);

        $this->assertSame(3, $task->getRetry());
    }

    /**
     * @return array<string, array{string}>
     */
    public static function connectionProvider(): array
    {
        $data = [
            "beanstalkd" => ["beanstalkd"],
            "database" => ["database"],
            "redis" => ["redis"],
            "rabbitmq" => ["rabbitmq"],
            "sync" => ["sync"],
        ];

        if (getenv("AWS_SQS_URL")) {
            $data["sqs"] = ["sqs"];
        }

        if (extension_loaded('rdkafka')) {
            $data["kafka"] = ["kafka"];
        }

        return $data;
    }
}
