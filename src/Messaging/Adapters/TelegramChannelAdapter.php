<?php

namespace Bow\Messaging\Adapters;

use Bow\Database\Barry\Model;
use Bow\Messaging\Contracts\ChannelAdapterInterface;
use Bow\Messaging\Messaging;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use RuntimeException;

class TelegramChannelAdapter implements ChannelAdapterInterface
{
    /**
     * @var string
     */
    private string $botToken;

    /**
     * Constructor
     *
     * @throws InvalidArgumentException When Telegram bot token is missing
     */
    public function __construct()
    {
        $this->botToken = config('messaging.telegram.bot_token');

        if (!$this->botToken) {
            throw new InvalidArgumentException('The Telegram bot token is required');
        }
    }

    /**
     * Envoyer le message via Telegram
     *
     * @param Model $context
     * @param Messaging $message
     * @return void
     * @throws GuzzleException
     */
    public function send(Model $context, Messaging $message): void
    {
        if (!method_exists($message, 'toTelegram')) {
            return;
        }

        $data = $message->toTelegram($context);

        if (!isset($data['chat_id']) || !isset($data['message'])) {
            throw new InvalidArgumentException('The chat ID and message are required for Telegram');
        }

        $client = new Client();
        $endpoint = "https://api.telegram.org/bot{$this->botToken}/sendMessage";

        try {
            $client->post($endpoint, [
                'json' => [
                    'chat_id' => $data['chat_id'],
                    'text' => $data['message'],
                    'parse_mode' => $data['parse_mode'] ?? 'HTML'
                ]
            ]);
        } catch (Exception $e) {
            throw new RuntimeException('Error while sending Telegram message: ' . $e->getMessage());
        }
    }
}
