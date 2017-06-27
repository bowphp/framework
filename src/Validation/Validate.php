<?php
namespace Bow\Validation;

use Bow\Validation\Exception\ValidationException;
/**
 * Class Validate
 *
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Support\Validate
 */
class Validate
{
    /**
     * @var bool
     */
    private $fails;

    /**
     * @var string
     */
    private $lastMessage = null;

    /**
     * @var array
     */
    private $messages = [];

    /**
     * @var array
     */
    private $corruptesFields = [];

    /**
     * @var array
     */
    private $corruptesRules = [];


    /**
     * Validate constructor.
     *
     * @param bool $fails
     * @param string $message
     * @param array $corruptesFields
     */
    public function __construct($fails, $message, array $corruptesFields)
    {
        $this->fails = $fails;
        $this->lastMessage = $message;
        $this->corruptesFields = array_keys($corruptesFields);
        $this->corruptesRules = [];
        $this->messages = [];

        foreach($corruptesFields as $key => $corruptes) {
            foreach($corruptes as $fields) {
                $this->messages[$key] = $fields["message"];
                $this->corruptesRules[$key] = $fields["masque"];
            }
        }
    }

    /**
     * Permet de conaitre l'état de la validation
     *
     * @return bool
     */
    public function fails()
    {
        return $this->fails;
    }

    /**
     * Informe sur les champs qui n'ont pas pu ètre valider
     *
     * @return array
     */
    public function getCorrupteFields()
    {
        return $this->corruptesFields;
    }

    /**
     * Le message d'erreur sur la dernière validation
     *
     * @return array
     */
    public function getFailsRules()
    {
        return $this->corruptesRules;
    }

    /**
     * Le message d'erreur sur la dernière validation
     *
     * @return string
     */
    public function getLastMessage()
    {
        return $this->lastMessage;
    }

    /**
     * Le message d'erreur sur la dernière validation
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @throws ValidationException
     */
    public function throwError()
    {
        throw new ValidationException(implode(', ', $this->messages), E_USER_ERROR);
    }
}