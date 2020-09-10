<?php

namespace Bow\Http\Exception;

use Bow\Http\Exception\HttpException;

class NoContentException extends HttpException
{
    /**
     * NoContentException constructor
     *
     * @param string $message
     * @param string $status
     */
    public function __construct(string $message, $status = 'NO_CONTENT')
    {
        parent::__construct($message, 204);

        $this->status = $status;
    }
}
