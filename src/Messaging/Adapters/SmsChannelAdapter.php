<?php

namespace Bow\Messaging\Adapters;

use Bow\Database\Barry\Model;
use Bow\Messaging\Contracts\ChannelAdapterInterface;
use Bow\Messaging\Messaging;
use InvalidArgumentException;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Rest\Client;

class SmsChannelAdapter implements ChannelAdapterInterface
{
    /**
     * @var Client
     */
    private Client $client;

    /**
     * @var string
     */
    private string $from_number;

    /**
     * Constructor
     *
     * @throws InvalidArgumentException|ConfigurationException When Twilio credentials are missing
     */
    public function __construct()
    {
        $account_sid = config('messaging.twilio.account_sid');
        $auth_token = config('messaging.twilio.auth_token');
        $this->from_number = config('messaging.twilio.from');

        if (!$account_sid || !$auth_token || !$this->from_number) {
            throw new InvalidArgumentException('Twilio credentials are required');
        }

        $this->client = new Client($account_sid, $auth_token);
    }

    /**
     * Send message via SMS
     *
     * @param Model $context
     * @param Messaging $message
     * @return void
     */
    public function send(Model $context, Messaging $message): void
    {
        if (!method_exists($message, 'toSms')) {
            return;
        }

        $this->sendWithTwilio($context, $message);
    }

    /**
     * Send the message via SMS using Twilio
     *
     * @param Model $context
     * @param Messaging $message
     * @return void
     */
    private function sendWithTwilio(Model $context, Messaging $message): void
    {
        $data = $message->toSms($context);

        $account_sid = config('messaging.twilio.account_sid');
        $auth_token = config('messaging.twilio.auth_token');
        $this->from_number = config('messaging.twilio.from');

        if (!$account_sid || !$auth_token || !$this->from_number) {
            throw new InvalidArgumentException('Twilio credentials are required');
        }

        $this->client = new Client($account_sid, $auth_token);

        if (!isset($data['to']) || !isset($data['message'])) {
            throw new InvalidArgumentException('The phone number and message are required');
        }

        try {
            $this->client->messages->create($data['to'], [
                'from' => $this->from_number,
                'body' => $data['message']
            ]);
        } catch (\Exception $e) {
            throw new \RuntimeException('Error while sending SMS: ' . $e->getMessage());
        }
    }
}
