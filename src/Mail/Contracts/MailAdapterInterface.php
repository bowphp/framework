<?php

declare(strict_types=1);

namespace Bow\Mail\Contracts;

use Bow\Mail\Envelop;

interface MailAdapterInterface
{
    /**
     * Send mail by any driver
     *
     * @param Envelop $envelop
     * @return bool
     */
    public function send(Envelop $envelop): bool;
}
