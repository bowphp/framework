<?php

declare(strict_types=1);

namespace Bow\Validation\Exception;

use Bow\Http\Exception\HttpException;

class ValidationException extends HttpException
{
    /**
     * The validation error fields
     *
     * @var array
     */
    private $errors = [];

    /**
     * ValidationException constructor
     *
     * @param string $message
     * @param array $errors
     * @param string $status
     */
    public function __construct(
        string $message,
        array  $errors = [],
        string $status = 'VALIDATION_ERROR'
    )
    {
        parent::__construct($message, 400);
        $this->errors = $errors;
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
}
