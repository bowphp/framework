<?php

declare(strict_types=1);

namespace Bow\Http\Exception;

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
