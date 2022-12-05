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
     * @param string $status
     * @param array $errors
     */
    public function __construct(
        string $message,
        string $status = 'VALIDATION_ERROR',
        array $errors = []
    ) {
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
