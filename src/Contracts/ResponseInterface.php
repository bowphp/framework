<?php

namespace Bow\Contracts;

interface ResponseInterface
{
    /**
     * Send Response to client
     *
     * @return string
     */
    public function sendContent();
}
