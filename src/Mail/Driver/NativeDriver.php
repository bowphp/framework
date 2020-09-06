<?php

namespace Bow\Mail\Driver;

use Bow\Mail\Contracts\MailDriverInterface;
use Bow\Mail\Message;
use Bow\Support\Str;
use InvalidArgumentException;

class NativeDriver extends MailDriverInterface
{
    /**
     * The configuration
     *
     * @var array
     */
    private $config;

    /**
     * SimpleMail Constructor
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Implement send email
     *
     * @param  Message $message
     * @throws InvalidArgumentException
     * @return bool
     */
    public function send(Message $message)
    {
        if (empty($message->getTo()) || empty($message->getSubject()) || empty($message->getMessage())) {
            throw new InvalidArgumentException(
                "An error has occurred. The sender or the message or object omits.",
                E_USER_ERROR
            );
        }

        if (isset($this->config['mail'])) {
            $section = $this->config['mail']['default'];

            if (!$message->fromIsDefined()) {
                $form = $this->config['mail'][$section];

                $message->from($form["address"], $form["name"]);
            } elseif (!Str::isMail($message->getFrom())) {
                $form = $this->config['mail'][$message->getFrom()];

                $message->from($form["address"], $form["name"]);
            }
        }

        $to = '';

        $message->setDefaultHeader();

        foreach ($message->getTo() as $value) {
            if ($value[0] !== null) {
                $to .= $value[0] . ' <' . $value[1] . '>';
            } else {
                $to .= '<' . $value[1] . '>';
            }
        }

        $headers = $message->compileHeaders();

        $headers .= 'Content-Type: ' . $message->getType() . '; charset=' . $message->getCharset() . Message::END;

        $headers .= 'Content-Transfer-Encoding: 8bit' . Message::END;

        // Send email use the php native function
        $status = @mail($to, $message->getSubject(), $message->getMessage(), $headers);

        return (bool) $status;
    }
}
