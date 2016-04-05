<?php

namespace Bow\Database;

use Bow\Exception\DatabaseException;

class DatabaseErrorHandler
{
    /**
     * @var array
     */
    private $pdoStatement;

    /**
     * @var string
     */
    private $driverCode = null;

    /**
     * @var string
     */
    private $driverMessage = null;

    /**
     * @var bool
     */
    private $error = false;

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->pdoStatement = $config;
        $this->formatError();
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->pdoStatement[0];
    }

    /**
     *
     */
    private function formatError()
    {
        if ($this->pdoStatement[1] !== null) {
            $this->driverMessage = $this->pdoStatement[2];
            $this->driverCode = $this->pdoStatement[0];
            $this->error = true;
        }
    }

    /**
     * @return bool
     */
    public function hasError()
    {
        return $this->error;
    }

    /**
     * @return string
     */
    public function getMessage()
    {
        return $this->driverMessage;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return $this->pdoStatement;
    }

    /**
     * @throws DatabaseException
     */
    public function throwError() {
        throw new DatabaseException($this->getMessage(), E_USER_ERROR);
    }
}