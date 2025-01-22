<?php

declare(strict_types=1);

namespace Bow\Http\Exception;

class UnauthorizedException extends HttpException
{
    /**
     * UnauthorizedException constructor
     *
     * @param string $message
     * @param string $status
     */
    public function __construct(string $message, $status = 'UNAUTHORIZED')
    {
        parent::__construct($message, 401);

        $this->status = $status;
    }
}
