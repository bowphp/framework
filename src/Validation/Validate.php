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
    private $corrupted_fields = [];

    /**
     * The corrupted rule list
     *
     * @var array
     */
    private $corrupted_rules = [];


    /**
     * Validate constructor.
     *
     * @param bool   $fails
     * @param string $message
     * @param array  $corrupted_fields
     *
     * @return void
     */
    public function __construct($fails, $message, array $corrupted_fields)
    {
        $this->fails = $fails;
        $this->last_message = $message;
        $this->corrupted_fields = array_keys($corrupted_fields);
        $this->corrupted_rules = [];
        $this->messages = [];

        foreach ($corrupted_fields as $key => $corrupted) {
            foreach ($corrupted as $fields) {
                $this->messages[$key] = $fields["message"];
                $this->corrupted_rules[$key] = $fields["masque"];
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
    public function getCorruptedFields()
    {
        return $this->corrupted_fields;
    }

    /**
     * The error message on the last commit
     *
     * @return array
     */
    public function getFailsRules()
    {
        return $this->corrupted_rules;
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
        response()->status(400);

        throw new ValidationException(implode(', ', $this->messages), E_USER_ERROR);
    }
}
