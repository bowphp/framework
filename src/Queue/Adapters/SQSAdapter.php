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
     * Push a task onto the queue
     *
     * @param  QueueTask $task
     * @return bool
     */
    public function push(QueueTask $task): bool
    {
        $task->setId($this->generateId());

        $params = [
            "DelaySeconds" => $task->getDelay(),
            "MessageAttributes" => $this->buildMessageAttributes($task),
            "MessageBody" => base64_encode($this->serializeProducer($task)),
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
     * @param  QueueTask $task
     * @return array
     */
    private function buildMessageAttributes(QueueTask $task): array
    {
        return [
            "Title" => [
                "DataType" => "String",
                "StringValue" => get_class($task),
            ],
            "Id" => [
                "DataType" => "String",
                "StringValue" => $task->getId(),
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
     * Process the next task on the queue
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
        $task = null;

        try {
            $task = $this->unserializeProducer(base64_decode($message["Body"]));
            $this->logProcessingTask($task);
            $task->process();
            $this->logProcessedTask($task);
            $this->deleteMessage($message);
        } catch (Throwable $e) {
            $this->handleMessageFailure($message, $task, $e);
        }
    }

    /**
     * Handle message processing failure
     *
     * @param  array $message
     * @param  QueueTask|null $task
     * @param  Throwable $exception
     * @return void
     */
    private function handleMessageFailure(array $message, ?QueueTask $task, Throwable $exception): void
    {
        $this->logError($exception);

        cache("task:failed:" . $message["ReceiptHandle"], $message["Body"]);

        $this->logFailedTask($task, $exception);

        if (is_null($task)) {
            $this->sleep(1);
            return;
        }

        $task->onException($exception);

        if ($task->taskShouldBeDelete()) {
            $this->deleteMessage($message);
        } else {
            $this->changeMessageVisibility($message, $task);
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
     * @param  QueueTask $task
     * @return void
     */
    private function changeMessageVisibility(array $message, QueueTask $task): void
    {
        $this->sqs->changeMessageVisibilityBatch([
            "QueueUrl" => $this->getQueueUrl(),
            "Entries" => [
                [
                    "Id" => $task->getId(),
                    "ReceiptHandle" => $message["ReceiptHandle"],
                    "VisibilityTimeout" => $task->getDelay(),
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
}
