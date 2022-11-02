<?php

declare(strict_types=1);

namespace Bow\Http\Exception;

use ErrorException;

class ResponseException extends ErrorException
{
    /**
     * Define the http response code
     *
     * @var int
     */
    protected $code;

    /**
     * Set the http code
     *
     * @param int $code
     * @return void
     */
    public function setCode(int $code)
    {
        $this->code = $code;
    }
}
