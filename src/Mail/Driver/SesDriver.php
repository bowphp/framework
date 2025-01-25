<?php

declare(strict_types=1);

namespace Bow\Mail\Driver;

use Aws\Ses\SesClient;
use Bow\Mail\Contracts\MailDriverInterface;
use Bow\Mail\Envelop;

class SesDriver implements MailDriverInterface
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
     * SesDriver constructor
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
                'ToAddresses' => $envelop->getTo(),
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
