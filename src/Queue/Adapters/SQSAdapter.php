<?php

namespace Bow\Queue\Adapters;

use Aws\Exception\AwsException;
use Aws\Sqs\SqsClient;
use Bow\Queue\QueueJob;
use ErrorException;
use RuntimeException;

class SQSAdapter extends QueueAdapter
{
    /**
     * The SQS client
     *
     * @var SqsClient
     */
    private SqsClient $sqs;

    /**
     * The configuration array
     *
     * @var array
     */
    private array $config = [];

    /**
     * Configure the queue.
     *
     * @param  array $config
     * @return QueueAdapter
     */
    public function configure(array $config): QueueAdapter
    {
        if (!class_exists(SqsClient::class)) {
            throw new RuntimeException(
                "Please install the aws/aws-sdk-php package"
            );
        }

        $this->config = $config;

        $this->sqs = new SqsClient($config);

        return $this;
    }

    /**
     * Push a job onto the queue.
     *
     * @param  QueueJob $job
     * @return bool
     */
    public function push(QueueJob $job): bool
    {
        $params = [
            'DelaySeconds' => $job->getDelay(),
            'MessageAttributes' => [
                "Title" => [
                    'DataType' => "String",
                    'StringValue' => get_class($job)
                ],
                "Id" => [
                    "DataType" => "String",
                    "StringValue" => $this->generateId(),
                ]
            ],
            'MessageBody' => base64_encode($this->serializeProducer($job)),
            'QueueUrl' => $this->config["url"]
        ];

        try {
            $this->sqs->sendMessage($params);
            return true;
        } catch (AwsException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    /**
     * Get the size of the queue.
     *
     * @param  string $queue
     * @return int
     */
    public function size(string $queue): int
    {
        $response = $this->sqs->getQueueAttributes([
            'QueueUrl' => $this->getQueue($queue),
            'AttributeNames' => ['ApproximateNumberOfMessages'],
        ]);

        $attributes = $response->get('Attributes');

        return (int)$attributes['ApproximateNumberOfMessages'];
    }

    /**
     * Process the next job on the queue.
     *
     * @param  ?string $queue
     * @return void
     * @throws ErrorException
     */
    public function run(?string $queue = null): void
    {
        $this->sleep($this->sleep ?? 5);
        $message = null;
        $delay = 5;

        try {
            $result = $this->sqs->receiveMessage([
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
            $job = $this->unserializeProducer(base64_decode($message["Body"]));
            $delay = $job->getDelay();
            call_user_func([$job, "process"]);
            $result = $this->sqs->deleteMessage([
                'QueueUrl' => $this->config["url"],
                'ReceiptHandle' => $message['ReceiptHandle']
            ]);
        } catch (AwsException $e) {
            // Write the error log
            error_log($e->getMessage());
            app('logger')->error($e->getMessage(), $e->getTrace());

            if (!$message) {
                $this->sleep(1);
                return;
            }

            cache("job:failed:" . $message["ReceiptHandle"], $message["Body"]);

            // Check if job has been loaded
            if (!isset($job)) {
                $this->sleep(1);
                return;
            }

            // Execute the onException method for notify the job
            // and let developer decide if the job should be deleted
            $job->onException($e);

            // Check if the job should be deleted
            if ($job->jobShouldBeDelete()) {
                $this->sqs->deleteMessage([
                    'QueueUrl' => $this->config["url"],
                    'ReceiptHandle' => $message['ReceiptHandle']
                ]);
            } else {
                $this->sqs->changeMessageVisibilityBatch([
                    'QueueUrl' => $this->config["url"],
                    'Entries' => [
                        'Id' => $job->getId(),
                        'ReceiptHandle' => $message['ReceiptHandle'],
                        'VisibilityTimeout' => $delay
                    ],
                ]);
            }

            $this->sleep(1);
        }
    }
}
