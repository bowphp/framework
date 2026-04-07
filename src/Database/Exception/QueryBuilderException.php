<?php

declare(strict_types=1);

namespace Bow\Database\Exception;

use ErrorException;

class QueryBuilderException extends ErrorException
{
    protected string $query;

    public function __construct(
        string $message,
        string $query = '',
        int $code = 0,
        int $severity = E_ERROR,
        ?string $filename = null,
        ?int $line = null
    ) {
        parent::__construct($message, $code, $severity, $filename, $line);
        $this->query = $query;
    }
}
