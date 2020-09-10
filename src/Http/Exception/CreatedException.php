<?php

namespace Bow\Http\Exception;

use Bow\Http\Exception\HttpException;

class CreatedException extends HttpException
{
    /**
     * CreatedException constructor
     *
     * @param string $message
     * @param string $status
     */
    public function __construct(string $message, $status = 'CONTENT_CREATED')
    {
        parent::__construct($message, 201);

        $this->status = $status;
    }
}
