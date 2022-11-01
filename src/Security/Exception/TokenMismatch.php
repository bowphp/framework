<?php

declare(strict_types=1);

namespace Bow\Security\Exception;

use Bow\Http\Exception\HttpException;

class TokenMismatch extends HttpException
{
    /**
     * TokenMismatch constructor
     *
     * @param string $message
     * @param string $status
     */
    public function __construct(string $message, $status = 'TOKEN_NOT_VALID')
    {
        parent::__construct($message, 500);

        $this->status = $status;
    }
}
