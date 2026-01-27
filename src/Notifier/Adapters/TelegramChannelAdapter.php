<?php

namespace Bow\Notifier\Adapters;

use Bow\Database\Barry\Model;
use Bow\Http\Client\HttpClient;
use Bow\Notifier\Contracts\ChannelAdapterInterface;
use Bow\Notifier\Notifier;
use Exception;
use InvalidArgumentException;
use RuntimeException;

class TelegramChannelAdapter implements ChannelAdapterInterface
{
    /**
     * @var string
     */
    private ?string $botToken;

    /**
     * Constructor
     *
     * @throws InvalidArgumentException When Telegram bot token is missing
     */
    public function __construct()
    {
        $this->botToken = config('messaging.telegram.bot_token');
    }

    /**
     * Envoyer le message via Telegram
     *
     * @param  Model     $context
     * @param  Notifier $notifier
     * @return void
     * @throws Exception
     */
    public function send(Model $context, Notifier $notifier): void
    {
        if (!method_exists($notifier, 'toTelegram')) {
            return;
        }

        $data = $notifier->toTelegram($context);

        if (!isset($data['chat_id']) || !isset($data['message'])) {
            throw new InvalidArgumentException('The chat ID and message are required for Telegram');
        }

        if (!$this->botToken) {
            throw new InvalidArgumentException('The Telegram bot token is required');
        }

        $client = new HttpClient();
        $endpoint = "https://api.telegram.org/bot{$this->botToken}/sendMessage";

        try {
            $client->acceptJson()->post($endpoint, [
                'chat_id' => $data['chat_id'],
                'text' => $data['message'],
                'parse_mode' => $data['parse_mode'] ?? 'HTML'
            ]);
        } catch (Exception $e) {
            throw new RuntimeException('Error while sending Telegram message: ' . $e->getMessage());
        }
    }
}
