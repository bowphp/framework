<?php

namespace Bow\Validation;

use BadMethodCallException;
use Bow\Http\Request;
use Bow\Validation\Exception\AuthorizationException;
use Bow\Validation\Exception\ValidationException;

abstract class ValidationRequest
{
    /**
     * The Validate instance
     *
     * @var Validate
     */
    private $validate;

    /**
     * The request data
     *
     * @var array
     */
    private $data;

    /**
     * The Request instance
     *
     * @var Request
     */
    private $request;

    /**
     * TodoValidation constructor.
     *
     * @return mixed
     * @throws
     */
    public function __construct()
    {
        if (!$this->authorize()) {
            $response = $this->authorizationFailAction();

            if (is_array($response) || is_object($response)) {
                $response = json_encode($response);
            }

            die($response);
        }

        $this->request = app('request');

        $keys = $this->keys();

        if ((count($keys) == 1 && $keys[0] === '*') || count($keys) == 0) {
            $this->data = $this->request->input()->all();
        } else {
            $this->data = $this->request->input()->excepts($keys);
        }

        $this->validate = Validator::make($this->data, $this->rules());

        if ($this->validate->fails()) {
            return $this->validationFailAction();
        }
    }

    /**
     * The rules list
     *
     * @return array
     */
    protected function rules()
    {
        return [
            // Your rules
        ];
    }

    /**
     * The allowed validation key
     *
     * @return array
     */
    protected function keys()
    {
        return [
            '*'
        ];
    }

    /**
     * The define the user authorisation
     *
     * @return bool
     */
    protected function authorize()
    {
        return true;
    }

    /**
     * When the user does not have the authorization to launch this request
     * This is the method that is launched to block the user
     *
     * @throws AuthorizationException
     */
    protected function authorizationFailAction()
    {
        throw new AuthorizationException(
            'You do not have permission to make a request'
        );
    }

    /**
     * Send fails authorization
     *
     * @throws AuthorizationException
     */
    protected function sendFailAuthorization()
    {
        throw new AuthorizationException(
            'You do not have permission to make a request'
        );
    }

    /**
     * Throw validation error
     *
     * @throws ValidationException
     */
    protected function sendFailValidation()
    {
        throw new ValidationException('Validation error');
    }

    /**
     * Quand l'utilisateur n'a pas l'authorization de lance cette requête
     * C'est la methode qui est lancer pour bloquer l'utilisateur
     *
     * @throws AuthorizationException
     */
    protected function validationFailAction()
    {
        //
    }

    /**
     * Check if the query
     *
     * @return boolean
     */
    protected function fails()
    {
        return $this->validate->fails();
    }

    /**
     * Get the validator instance
     *
     * @return Validate
     */
    protected function getValidationInstance()
    {
        return $this->validate;
    }

    /**
     * Get the message of the last error
     *
     * @return string
     */
    protected function getMessage()
    {
        return $this->validate->getLastMessage();
    }

    /**
     * Get all errors messages
     *
     * @return array
     */
    protected function getMessages()
    {
        return $this->validate->getMessages();
    }

    /**
     * Get validation data
     *
     * @return array
     */
    protected function getValidationData()
    {
        return $this->data;
    }

    /**
     * Throws an exception
     *
     * @throws ValidationException;
     */
    protected function throwError()
    {
        $this->validate->throwError();
    }

    /**
     * __call
     *
     * @param  string $name
     * @param  array  $arguments
     * @return Request
     */
    public function __call($name, array $arguments)
    {
        if (method_exists($this->request, $name)) {
            return call_user_func_array([$this->request, $name], $arguments);
        }

        throw new BadMethodCallException('The method '. $name.' does not define.');
    }

    /**
     * __get
     *
     * @param  string $name
     * @return string
     */
    public function __get($name)
    {
        return $this->request->$name;
    }
}
