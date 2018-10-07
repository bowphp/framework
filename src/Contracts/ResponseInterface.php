<?php

namespace Bow\Contracts;

interface ResponseInterface
{
    /**
     * Send Response to client
     *
     * @return mixed
     */
    public function sendContent();
}
