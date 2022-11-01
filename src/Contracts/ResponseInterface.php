<?php

declare(strict_types=1);

namespace Bow\Contracts;

interface ResponseInterface
{
    /**
     * Send Response to client
     *
     * @return void
     */
    public function sendContent(): void;
}
