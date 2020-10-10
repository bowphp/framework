<?php

namespace Bow\Http\Exception;

use Exception;

class HttpException extends Exception
{
    /**
     * Define the status code has message
     *
     * @var string
     */
    protected $status = 'OK';

    /**
     * HttpException constructor
     *
     * @param string $message
     * @param string $code
     */
    public function __construct(string $message, $code = 200)
    {
        response()->status($code);
        
        parent::__construct($message, $code);
    }

    /**
     * Get the define user error code
     *
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Get the status code
     *
     * @return string
     */
    public function getStatusCode()
    {
        return $this->getCode();
    }
}
