<?php

namespace Bow\Notifier\Adapters;

use Bow\Database\Barry\Model;
use Bow\Http\Client\HttpClient;
use Bow\Notifier\Contracts\ChannelAdapterInterface;
use Bow\Notifier\Notifier;

class SlackChannelAdapter implements ChannelAdapterInterface
{
    /**
     * Send notifier via Slack
     *
     * @param  Model     $context
     * @param  Notifier $notifier
     * @return void
     * @throws \Exception
     */
    public function send(Model $context, Notifier $notifier): void
    {
        if (!method_exists($notifier, 'toSlack')) {
            return;
        }

        $data = $notifier->toSlack($context);

        if (!isset($data['content'])) {
            throw new \InvalidArgumentException('The content are required for Slack');
        }

        $webhook_url = $data['webhook_url'] ?? config('messaging.slack.webhook_url');

        if (empty($webhook_url)) {
            throw new \InvalidArgumentException('The webhook URL is required for Slack');
        }

        $client = new HttpClient();

        try {
            $client->acceptJson()->post($webhook_url, $data['content']);
        } catch (\Exception $e) {
            throw new \RuntimeException('Error while sending Slack notifier: ' . $e->getMessage());
        }
    }
}
