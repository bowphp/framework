<?php

namespace Bow\Mail\Driver;

use Aws\Ses\SesClient;
use Bow\Mail\Message;
use Bow\Mail\Contracts\MailDriverInterface;

class SesDriver extends MailDriverInterface
{
    /**
    * The SES Instance
    *
    * @var SesClient
    */
    private $ses;

    private $config_set = false;

    /**
    * SesDriver constructor
    *
    * @param array $config
    * @return void
    */
    public function __construct(array $config)
    {
        $this->config_set = $config["config_set"] ?? false;
        unset($config["config_set"]);
        $this->ses = new SesClient($config);
    }

    /**
    * Send message
    *
    * @param Message $message
    * @return mixed
    */
    public function send(Message $message)
    {
        $body = [];

        if ($message->getType() == "text/html") {
            $type = "Html";
        } else {
            $type = "Text";
        }

        $body[$type] = [
            'Charset' => $message->getCharset(),
            'Data' => $message->getMessage(),
        ];

        $subject = [
            'Charset' => $message->getCharset(),
            'Data' => $message->getSubject(),
        ];

        $email = [
            'Destination' => [
                'ToAddresses' => $message->getTo(),
            ],
            'Source' => $message->getFrom(),
            'Message' => [
                'Body' => $body,
                'Subject' => $subject,
            ],
        ];

        if ($this->config_set) {
            $email["ConfigurationSetName"] = $this->config_set;
        }

        $result = $this->ses->sendEmail($email);

        return $result;
    }
}
