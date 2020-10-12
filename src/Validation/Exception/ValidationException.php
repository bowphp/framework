<?php

namespace Bow\Validation\Exception;

use Bow\Http\Exception\HttpException;

class ValidationException extends HttpException
{
    /**
     * ValidationException constructor
     *
     * @param string $message
     * @param string $status
     */
    public function __construct(string $message, $status = 'VALIDATION_ERROR')
    {
        parent::__construct($message, 400);

        $this->status = $status;
    }
}
