<?php

declare(strict_types=1);

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
     * Define the errors bags
     *
     * @var array
     */
    protected array $error_bags = [];

    /**
     * HttpException constructor
     *
     * @param string $message
     * @param string $code
     */
    public function __construct(string $message, int $code = 200)
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

    /**
     * Set the errors bags
     *
     * @param array $errors
     */
    public function setErrorBags(array $errors)
    {
        $this->error_bags = $errors;
    }

    /**
     * Get the errors bags
     *
     * @return array
     */
    public function getErrorBags(): array
    {
        return $this->error_bags;
    }
}
