<?php

declare(strict_types=1);

namespace Bow\Http\Exception;

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
