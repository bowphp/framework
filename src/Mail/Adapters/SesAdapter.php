<?php

declare(strict_types=1);

namespace Bow\Mail\Adapters;

use Aws\Ses\SesClient;
use Bow\Mail\Contracts\MailAdapterInterface;
use Bow\Mail\Envelop;

class SesAdapter implements MailAdapterInterface
{
    /**
     * The SES Instance
     *
     * @var SesClient
     */
    private SesClient $ses;

    /**
     * Ses internal config
     *
     * @var bool
     */
    private bool $config_set = false;

    /**
     * SesAdapter constructor
     *
     * @param array $config
     * @return void
     */
    public function __construct(private array $config)
    {
        $this->config_set = $this->config["config_set"] ?? false;

        unset($this->config["config_set"]);

        $this->initializeSesClient();
    }

    /**
     * Get the SES Instance
     *
     * @return SesClient
     */
    public function initializeSesClient(): SesClient
    {
        $this->ses = new SesClient($this->config);

        return $this->ses;
    }

    /**
     * Send env$envelop
     *
     * @param Envelop $envelop
     * @return bool
     */
    public function send(Envelop $envelop): bool
    {
        $body = [];

        if ($envelop->getType() == "text/html") {
            $type = "Html";
        } else {
            $type = "Text";
        }

        $body[$type] = [
            'Charset' => $envelop->getCharset(),
            'Data' => $envelop->getMessage(),
        ];

        $subject = [
            'Charset' => $envelop->getCharset(),
            'Data' => $envelop->getSubject(),
        ];

        $email = [
            'Destination' => [
                'ToAddresses' => array_map(fn ($value) => $value[0] !== null ? $value[0] . ' <' . $value[1] . '>' : '<' . $value[1] . '>', $envelop->getTo()),
            ],
            'Source' => $envelop->getFrom(),
            'Envelop' => [
                'Body' => $body,
                'Subject' => $subject,
            ],
        ];

        if ($this->config_set) {
            $email["ConfigurationSetName"] = $this->config_set;
        }

        $result = $this->ses->sendEmail($email);

        return (bool)$result;
    }
}
