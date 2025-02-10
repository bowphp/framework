<?php

namespace Bow\Messaging\Adapters;

use Bow\Database\Barry\Model;
use Bow\Messaging\Contracts\ChannelAdapterInterface;
use Bow\Messaging\Messaging;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class SlackChannelAdapter implements ChannelAdapterInterface
{
    /**
     * Send message via Slack
     *
     * @param Model $context
     * @param Messaging $message
     * @return void
     * @throws GuzzleException
     */
    public function send(Model $context, Messaging $message): void
    {
        if (!method_exists($message, 'toSlack')) {
            return;
        }

        $data = $message->toSlack($context);

        if (!isset($data['content'])) {
            throw new \InvalidArgumentException('The content are required for Slack');
        }

        $webhook_url = $data['webhook_url'] ?? config('messaging.slack.webhook_url');

        if (empty($webhook_url)) {
            throw new \InvalidArgumentException('The webhook URL is required for Slack');
        }

        $client = new Client();

        try {
            $client->post($webhook_url, [
                'json' => $data['content'],
                'headers' => [
                    'Content-Type' => 'application/json'
                ]
            ]);
        } catch (\Exception $e) {
            throw new \RuntimeException('Error while sending Slack message: ' . $e->getMessage());
        }
    }
}
