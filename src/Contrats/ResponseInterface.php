<?php

namespace Bow\Contrats;

interface ResponseInterface
{
    /**
     * Send Response to client
     *
     * @return mixed
     */
    public function send();
}
