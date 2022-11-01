<?php

declare(strict_types=1);

namespace Bow\Http\Exception;

use Bow\Http\Exception\HttpException;

class ForbiddenException extends HttpException
{
    /**
     * ForbiddenException constructor
     *
     * @param string $message
     * @param string $status
     */
    public function __construct(string $message, $status = 'FORBIDDEN')
    {
        parent::__construct($message, 403);

        $this->status = $status;
    }
}
