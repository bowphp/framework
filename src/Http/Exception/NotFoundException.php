<?php

namespace Bow\Http\Exception;

use ErrorException;

class NotFoundException extends HttpException
{
    /**
     * NotFoundException constructor
     *
     * @param string $message
     * @param string $status
     */
    public function __construct(string $message, $status = 'NOT_FOUND')
    {
        parent::__construct($message, 404);

        $this->status = $status;
    }
}
