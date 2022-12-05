<?php

declare(strict_types=1);

namespace Bow\Http\Exception;

use Bow\Http\Exception\HttpException;

class InternalServerErrorException extends HttpException
{
    /**
     * InternalServerErrorException constructor
     *
     * @param string $message
     * @param string $status
     */
    public function __construct(string $message, $status = 'INTERNAL_SERVER_ERROR')
    {
        parent::__construct($message, 500);

        $this->status = $status;
    }
}
