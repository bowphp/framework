<?php

namespace Bow\Notifier\Adapters;

use Bow\Database\Barry\Model;
use Bow\Http\Client\HttpClient;
use Bow\Notifier\Contracts\ChannelAdapterInterface;
use Bow\Notifier\Notifier;
use InvalidArgumentException;
use Twilio\Exceptions\ConfigurationException;
use Twilio\Rest\Client;

class SmsChannelAdapter implements ChannelAdapterInterface
{
    /**
     * @var string
     */
    private string $from_number;

    /**
     * The SMS provider
     *
     * @var string
     */
    private string $sms_provider;

    /**
     * The configuration array
     *
     * @var array
     */
    private array $setting;

    /**
     * Constructor
     *
     * @throws InvalidArgumentException|ConfigurationException When Twilio credentials are missing
     */
    public function __construct()
    {
        $config = config('notifier.sms');
        $this->setting = $config['setting'] ?? [];
        $this->sms_provider = $config['provider'] ?? 'callisto';
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

        if ($this->sms_provider === 'twilio') {
            $this->sendWithTwilio($context, $notifier);
            return;
        }

        if ($this->sms_provider === 'callisto') {
            $this->sendWithCallisto($context, $notifier);
            return;
        };
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

        $account_sid = $this->setting['account_sid'] ?? null;
        $auth_token = $this->setting['auth_token'] ?? null;
        $this->from_number = $this->setting['from'] ?? null;

        if (!$account_sid || !$auth_token || !$this->from_number) {
            throw new InvalidArgumentException('Twilio credentials are required');
        }

        if (!isset($data['to']) || !isset($data['message'])) {
            throw new InvalidArgumentException('The phone number and notifier are required');
        }

        try {
            $client = new Client($account_sid, $auth_token);
            $client->messages->create($data['to'], [
                'from' => $this->from_number,
                'body' => $data['message']
            ]);
        } catch (\Exception $e) {
            throw new \RuntimeException('Error while sending SMS: ' . $e->getMessage());
        }
    }

    /**
     * Send the notifier via SMS using Callisto
     *
     * @param  Model     $context
     * @param  Notifier $notifier
     * @return void
     */
    private function sendWithCallisto(Model $context, Notifier $notifier): void
    {
        $access_key = $this->setting['access_key'] ?? null;
        $access_secret = $this->setting['access_secret'] ?? null;
        $notify_url = $this->setting['notify_url'] ?? null;

        if (!$access_key || !$access_secret) {
            throw new InvalidArgumentException('Callisto credentials are required');
        }

        $data = $notifier->toSms($context);

        if (!isset($data['to']) || !isset($data['message']) || !isset($data['sender'])) {
            throw new InvalidArgumentException('The phone number and notifier are required');
        }

        $client = new HttpClient('https://api.callistosms.com');

        if (!isset($data['notify_url'])) {
            $data['notify_url'] = $notify_url;
        }

        $payload = [
            'to' => (array) $data['to'],
            'message' => $data['message'],
            'sender' => $data['sender'],
        ];

        if ($data['notify_url']) {
            $payload['notify_url'] = $data['notify_url'];
        }

        $client->basicAuth($access_key, $access_secret)
            ->acceptJson()
            ->post('v1/sms/send', $payload);
    }
}
