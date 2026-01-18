<?php

namespace Bow\Notifier\Adapters;

use Bow\Database\Barry\Model;
use Bow\Notifier\Contracts\ChannelAdapterInterface;
use Bow\Notifier\Notifier;
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
     * Send notifier via SMS
     *
     * @param  Model     $context
     * @param  Notifier $notifier
     * @return void
     */
    public function send(Model $context, Notifier $notifier): void
    {
        if (!method_exists($notifier, 'toSms')) {
            return;
        }

        $this->sendWithTwilio($context, $notifier);
    }

    /**
     * Send the notifier via SMS using Twilio
     *
     * @param  Model     $context
     * @param  Notifier $notifier
     * @return void
     */
    private function sendWithTwilio(Model $context, Notifier $notifier): void
    {
        $data = $notifier->toSms($context);

        $account_sid = config('notifier.twilio.account_sid');
        $auth_token = config('notifier.twilio.auth_token');
        $this->from_number = config('notifier.twilio.from');

        if (!$account_sid || !$auth_token || !$this->from_number) {
            throw new InvalidArgumentException('Twilio credentials are required');
        }

        $this->client = new Client($account_sid, $auth_token);

        if (!isset($data['to']) || !isset($data['message'])) {
            throw new InvalidArgumentException('The phone number and notifier are required');
        }

        try {
            $this->client->notifiers->create($data['to'], [
                'from' => $this->from_number,
                'body' => $data['notifier']
            ]);
        } catch (\Exception $e) {
            throw new \RuntimeException('Error while sending SMS: ' . $e->getMessage());
        }
    }
}
