<?php

declare(strict_types=1);

namespace Bow\Mail\Adapters;

use Bow\Mail\Contracts\MailAdapterInterface;
use Bow\Mail\Envelop;
use Bow\Mail\Exception\MailException;
use InvalidArgumentException;

class NativeAdapter implements MailAdapterInterface
{
    /**
     * The configuration
     *
     * @var array
     */
    private array $config;

    /**
     * The from configuration
     *
     * @var array
     */
    private array $from = [];

    /**
     * SimpleMail Constructor
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;

        if (count($config) > 0) {
            $this->from = $this->config["from"][$config["default"]];
        }
    }

    /**
     * Switch on other define from
     *
     * @param string $from
     * @return NativeAdapter
     * @throws MailException
     */
    public function on(string $from): NativeAdapter
    {
        if (!isset($this->config["froms"][$from])) {
            throw new MailException(
                "There are not entry for [$from]",
                E_USER_ERROR
            );
        }

        $this->from = $this->config["froms"][$from];

        return $this;
    }

    /**
     * Implement send email
     *
     * @param Envelop $envelop
     * @return bool
     * @throws InvalidArgumentException
     */
    public function send(Envelop $envelop): bool
    {
        if (empty($envelop->getTo()) || empty($envelop->getSubject()) || empty($envelop->getMessage())) {
            throw new InvalidArgumentException(
                "An error has occurred. The sender or the env$envelop or object omits.",
                E_USER_ERROR
            );
        }

        if (!$envelop->fromIsDefined()) {
            if (isset($this->from["address"])) {
                $envelop->from($this->from["address"], $this->from["name"] ?? null);
            }
        }

        $to = '';

        $envelop->setDefaultHeader();

        foreach ($envelop->getTo() as $value) {
            if ($value[0] !== null) {
                $to .= $value[0] . ' <' . $value[1] . '>';
            } else {
                $to .= '<' . $value[1] . '>';
            }
        }

        $headers = $envelop->compileHeaders();

        $headers .= 'Content-Type: ' . $envelop->getType() . '; charset=' . $envelop->getCharset() . Envelop::END;
        $headers .= 'Content-Transfer-Encoding: 8bit' . Envelop::END;

        // Send email use the php native function
        $status = @mail($to, $envelop->getSubject(), $envelop->getMessage(), $headers);

        return (bool)$status;
    }
}
