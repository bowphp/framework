<?php

declare(strict_types=1);

namespace Bow\Queue\Adapters;

use Aws\Exception\AwsException;
use Aws\Sqs\SqsClient;
use Bow\Queue\QueueTask;
use RuntimeException;
use Throwable;

class SQSAdapter extends QueueAdapter
{
    /**
     * Default wait time for long polling (in seconds)
     */
    private const WAIT_TIME_SECONDS = 20;

    /**
     * The SQS client instance
     *
     * @var SqsClient
     */
    private SqsClient $sqs;

    /**
     * The adapter configuration
     *
     * @var array
     */
    private array $config = [];

    /**
     * Configure the SQS queue adapter
     *
     * @param  array $config
     * @return SQSAdapter
     */
    public function configure(array $config): SQSAdapter
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
     * Push a job onto the queue
     *
     * @param  QueueTask $job
     * @return bool
     */
    public function push(QueueTask $job): bool
    {
        $params = [
            "DelaySeconds" => $job->getDelay(),
            "MessageAttributes" => $this->buildMessageAttributes($job),
            "MessageBody" => base64_encode($this->serializeProducer($job)),
            "QueueUrl" => $this->getQueueUrl(),
        ];

        try {
            $this->sqs->sendMessage($params);
            return true;
        } catch (AwsException $e) {
            $this->logError($e);
            return false;
        }
    }

    /**
     * Build message attributes for SQS
     *
     * @param  QueueTask $job
     * @return array
     */
    private function buildMessageAttributes(QueueTask $job): array
    {
        return [
            "Title" => [
                "DataType" => "String",
                "StringValue" => get_class($job),
            ],
            "Id" => [
                "DataType" => "String",
                "StringValue" => $this->generateId(),
            ],
        ];
    }

    /**
     * Get the size of the queue
     *
     * @param  string|null $queue
     * @return int
     */
    public function size(?string $queue = null): int
    {
        $response = $this->sqs->getQueueAttributes([
            "QueueUrl" => $this->getQueue($queue),
            "AttributeNames" => ["ApproximateNumberOfMessages"],
        ]);

        $attributes = $response->get("Attributes");

        return (int) $attributes["ApproximateNumberOfMessages"];
    }

    /**
     * Process the next job on the queue
     *
     * @param  string|null $queue
     * @return void
     */
    public function run(?string $queue = null): void
    {
        $this->sleep($this->sleep);

        $message = $this->receiveMessage();

        if (is_null($message)) {
            $this->sleep(1);
            return;
        }

        $this->processMessage($message);
    }

    /**
     * Receive a message from the queue
     *
     * @return array|null
     */
    private function receiveMessage(): ?array
    {
        $result = $this->sqs->receiveMessage([
            "AttributeNames" => ["SentTimestamp"],
            "MaxNumberOfMessages" => 1,
            "MessageAttributeNames" => ["All"],
            "QueueUrl" => $this->getQueueUrl(),
            "WaitTimeSeconds" => self::WAIT_TIME_SECONDS,
        ]);

        $messages = $result->get("Messages");

        return empty($messages) ? null : $messages[0];
    }

    /**
     * Process a single message from the queue
     *
     * @param  array $message
     * @return void
     */
    private function processMessage(array $message): void
    {
        $job = null;

        try {
            $job = $this->unserializeProducer(base64_decode($message["Body"]));
            call_user_func([$job, "process"]);
            $this->deleteMessage($message);
        } catch (Throwable $e) {
            $this->handleMessageFailure($message, $job, $e);
        }
    }

    /**
     * Handle message processing failure
     *
     * @param  array $message
     * @param  QueueTask|null $job
     * @param  Throwable $exception
     * @return void
     */
    private function handleMessageFailure(array $message, ?QueueTask $job, Throwable $exception): void
    {
        $this->logError($exception);
        cache("job:failed:" . $message["ReceiptHandle"], $message["Body"]);

        if (is_null($job)) {
            $this->sleep(1);
            return;
        }

        $job->onException($exception);

        if ($job->taskShouldBeDelete()) {
            $this->deleteMessage($message);
        } else {
            $this->changeMessageVisibility($message, $job);
        }

        $this->sleep(1);
    }

    /**
     * Delete a message from the queue
     *
     * @param  array $message
     * @return void
     */
    private function deleteMessage(array $message): void
    {
        $this->sqs->deleteMessage([
            "QueueUrl" => $this->getQueueUrl(),
            "ReceiptHandle" => $message["ReceiptHandle"],
        ]);
    }

    /**
     * Change message visibility for retry
     *
     * @param  array $message
     * @param  QueueTask $job
     * @return void
     */
    private function changeMessageVisibility(array $message, QueueTask $job): void
    {
        $this->sqs->changeMessageVisibilityBatch([
            "QueueUrl" => $this->getQueueUrl(),
            "Entries" => [
                [
                    "Id" => $job->getId(),
                    "ReceiptHandle" => $message["ReceiptHandle"],
                    "VisibilityTimeout" => $job->getDelay(),
                ],
            ],
        ]);
    }

    /**
     * Get the queue URL from configuration
     *
     * @return string
     */
    private function getQueueUrl(): string
    {
        return $this->config["url"];
    }

    /**
     * Log an error
     *
     * @param  Throwable $exception
     * @return void
     */
    private function logError(Throwable $exception): void
    {
        error_log($exception->getMessage());

        try {
            app("logger")->error($exception->getMessage(), $exception->getTrace());
        } catch (Throwable $loggerException) {
            // Logger not available, already logged to error_log
        }
    }
}
