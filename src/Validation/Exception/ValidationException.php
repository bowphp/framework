<?php

namespace Bow\Validation\Exception;

use Bow\Http\Exception\HttpException;

class ValidationException extends HttpException
{
    /**
     * The error collection
     *
     * @var array
     */
    private $errors = [];

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

    /**
     * Get the errors
     *
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Set the error
     *
     * @param array $errors
     */
    public function setErrors(array $errors)
    {
        $this->errors = $errors;
    }
}
