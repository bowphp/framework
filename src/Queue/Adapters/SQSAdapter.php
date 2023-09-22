<?php

namespace Bow\Queue\Adapters;

use Aws\Exception\AwsException;
use Aws\Sqs\SqsClient;
use Bow\Queue\Adapters\QueueAdapter;
use Bow\Queue\ProducerService;
use RuntimeException;

class SQSAdapter extends QueueAdapter
{
    /**
     * The SQS client
     *
     * @var SqsClient
     */
    private SqsClient $client;

    /**
     * The configuration array
     *
     * @var array
     */
    private array $config = [];

    /**
     * Determine the default watch name
     *
     * @var string
     */
    private string $queue = "default";

    /**
     * The number of working attempts
     *
     * @var int
     */
    private int $tries;

    /**
     * Define the sleep time
     *
     * @var int
     */
    private int $sleep = 5;

    /**
     * Configure the queue.
     *
     * @param array $config
     * @return QueueAdapter
     */
    public function configure(array $config): QueueAdapter
    {
        if (!class_exists(SqsClient::class)) {
            throw new RuntimeException("Please install the aws/aws-sdk-php package");
        }

        $this->config = $config;

        $this->client = new SqsClient([
            'profile' => 'default',
            'region' => 'us-west-2',
            'version' => '2012-11-05',
            'credentials' => [
                'key' => $config["key"],
                'secret' => $config["secret"]
            ]
        ]);

        return $this;
    }

    /**
     * Set the watch queue.
     *
     * @param string $queue
     * @return void
     */
    public function setWatch(string $queue): void
    {
        $this->queue = $queue;
    }

    /**
     * Set the number of times to attempt a job.
     *
     * @param int $tries
     * @return void
     */
    public function setTries(int $tries): void
    {
        $this->tries = $tries;
    }

    /**
     * Set the number of seconds to sleep between jobs.
     *
     * @param int $sleep
     * @return void
     */
    public function setSleep(int $sleep): void
    {
        $this->sleep = $sleep;
    }

    /**
     * Push a job onto the queue.
     *
     * @param ProducerService $producer
     * @return void
     */
    public function push(ProducerService $producer): void
    {
        $params = [
            'DelaySeconds' => $producer->getDelay(),
            'MessageAttributes' => [
                "Title" => [
                    'DataType' => "String",
                    'StringValue' => get_class($producer)
                ],
            ],
            'MessageBody' => $this->serializeProducer($producer),
            'QueueUrl' => $this->config["url"]
        ];

        try {
            $result = $this->client->sendMessage($params);
        } catch (AwsException $e) {
            error_log($e->getMessage());
        }
    }

    /**
     * Get the size of the queue.
     *
     * @param string $queue
     * @return int
     */
    public function size(string $queue): int
    {
        $response = $this->client->getQueueAttributes([
            'QueueUrl' => $this->getQueue($queue),
            'AttributeNames' => ['ApproximateNumberOfMessages'],
        ]);

        $attributes = $response->get('Attributes');

        return (int) $attributes['ApproximateNumberOfMessages'];
    }

    /**
     * Get the queue or return the default.
     *
     * @param ?string $queue
     * @return string
     */
    public function getQueue(?string $queue = null): string
    {
        return $queue ?: $this->queue;
    }

    /**
     * Set the number of seconds to wait before retrying a job.
     *
     * @param int $retry
     * @return void
     */
    public function setRetries(int $tries)
    {
        $this->tries = $tries;
    }

    /**
     * Process the next job on the queue.
     *
     * @param ?string $queue
     * @return void
     */
    public function run(?string $queue = null): void
    {
        $this->sleep($this->sleep ?? 5);

        try {
            $result = $this->client->receiveMessage([
                'AttributeNames' => ['SentTimestamp'],
                'MaxNumberOfMessages' => 1,
                'MessageAttributeNames' => ['All'],
                'QueueUrl' => $this->config["url"],
                'WaitTimeSeconds' => 20,
            ]);
            $messages = $result->get('Messages');
            if (empty($messages)) {
                $this->sleep(1);
                return;
            }
            $message = $result->get('Messages')[0];
            $producer = $this->unserializeProducer($message["MessageBody"]);
            $delay = $producer->getDelay();
            call_user_func([$producer, "process"]);
            $result = $this->client->deleteMessage([
                'QueueUrl' => $this->config["url"],
                'ReceiptHandle' => $message['ReceiptHandle']
            ]);
        } catch (AwsException $e) {
            error_log($e->getMessage());
            app('logger')->error($e->getMessage(), $e->getTrace());
            if ($producer->jobShouldBeDelete()) {
                $result = $this->client->deleteMessage([
                    'QueueUrl' => $this->config["url"],
                    'ReceiptHandle' => $message['ReceiptHandle']
                ]);
            } else {
                $result = $this->client->changeMessageVisibilityBatch([
                    'QueueUrl' => $this->config["url"],
                    'Entries' => [
                        'Id' => $producer->getId(),
                        'ReceiptHandle' => $message['ReceiptHandle'],
                        'VisibilityTimeout' => $delay
                    ],
                ]);
            }
            $this->sleep(1);
        }
    }
}
