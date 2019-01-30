<?php

namespace Bow\Validation;

use Bow\Validation\Exception\ValidationException;

class Validate
{
    /**
     * The validation fails flag
     *
     * @var bool
     */
    private $fails;

    /**
     * The last message
     *
     * @var string
     */
    private $last_message = null;

    /**
     * The error messages list
     *
     * @var array
     */
    private $messages = [];

    /**
     * The corrupted fields list
     *
     * @var array
     */
    private $corruptes_fields = [];

    /**
     * The corrupted rule list
     *
     * @var array
     */
    private $corruptes_rules = [];


    /**
     * Validate constructor.
     *
     * @param bool   $fails
     * @param string $message
     * @param array  $corruptes_fields
     */
    public function __construct($fails, $message, array $corruptes_fields)
    {
        $this->fails = $fails;
        $this->last_message = $message;
        $this->corruptes_fields = array_keys($corruptes_fields);
        $this->corruptes_rules = [];
        $this->messages = [];

        foreach ($corruptes_fields as $key => $corruptes) {
            foreach ($corruptes as $fields) {
                $this->messages[$key] = $fields["message"];
                $this->corruptes_rules[$key] = $fields["masque"];
            }
        }
    }

    /**
     * Allows to know the status of the validation
     *
     * @return bool
     */
    public function fails()
    {
        return $this->fails;
    }

    /**
     * Informs about fields that could not be validated
     *
     * @return array
     */
    public function getCorrupteFields()
    {
        return $this->corruptes_fields;
    }

    /**
     * The error message on the last commit
     *
     * @return array
     */
    public function getFailsRules()
    {
        return $this->corruptes_rules;
    }

    /**
     * The error message on the last commit
     *
     * @return string
     */
    public function getLastMessage()
    {
        return $this->last_message;
    }

    /**
     * The error message on the last commit
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Throw error
     *
     * @throws ValidationException
     */
    public function throwError()
    {
        throw new ValidationException(implode(', ', $this->messages), E_USER_ERROR);
    }
}
