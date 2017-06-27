<?php
namespace Bow\Database\Util;

use Bow\Database\Exception\DatabaseException;

/**
 * Class DatabaseErrorHandler
 *
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Database
 */
class Error
{
    /**
     * @var mixed
     */
    public $rowAffected = 0;

    /**
     * @var array
     */
    private $pdoStatement;

    /**
     * @var string
     */
    private $driverCode;

    /**
     * @var string
     */
    private $driverMessage;

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
        $this->format();
    }

    /**
     * Le code de l'erreur PDO
     *
     * @return string
     */
    public function getCode()
    {
        return $this->pdoStatement[0];
    }

    /**
     * formateur
     */
    private function format()
    {
        if (isset($this->pdoStatement[1]) && $this->pdoStatement[1] !== null) {
            $this->driverMessage = $this->pdoStatement[2];
            $this->driverCode = $this->pdoStatement[0];
            $this->error = true;
        }
    }

    /**
     * Donne l'information de l'existance d'une erreur.
     *
     * @return bool
     */
    public function hasError()
    {
        return $this->error;
    }

    /**
     * Le message de l'erreur PDO
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->driverMessage;
    }

    /**
     * Retourne l'erreur PDO similaire de PDO::errorInfo()
     *
     * @return array
     */
    public function toArray()
    {
        return $this->pdoStatement;
    }

    /**
     * Lance une exception qui a pour message le message de l'erreur PDO
     *
     * @param string|null $message
     * @throws DatabaseException
     */
    public function throwError($message = null)
    {
        if ($message === null) {
            $message = $this->getMessage();
        }

        throw new DatabaseException($message, E_USER_ERROR);
    }
}