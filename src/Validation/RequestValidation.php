<?php

namespace Bow\Validation;

use BadMethodCallException;
use Bow\Http\Request;
use Bow\Validation\Exception\AuthorizationException;

abstract class RequestValidation
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
            $this->authorizationFailAction();

            $this->sendFailAuthorization();
        }

        $this->request = app('request');

        $keys = $this->keys();

        if ((count($keys) == 1 && $keys[0] === '*') || count($keys) == 0) {
            $this->data = $this->request->all();
        } else {
            $this->data = $this->request->excepts($keys);
        }

        $this->validate = Validator::make($this->data, $this->rules(), $this->messages());

        if ($this->validate->fails()) {
            $this->validationFailAction();

            $this->validate->throwError();
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
     * The define the user authorization level
     *
     * @return bool
     */
    protected function authorize()
    {
        return true;
    }

    /**
     * The define the user custom message
     *
     * @return array
     */
    protected function messages()
    {
        return [];
    }

    /**
     * Send fails authorization
     *
     * @param mixed $response
     * @throws AuthorizationException
     */
    private function sendFailAuthorization($response = null)
    {
        throw new AuthorizationException(
            'You do not have permission to make a request'
        );
    }

    /**
     * When the user does not have the authorization to launch this request
     * This is hook the method that can watch them for make an action
     *
     * @throws AuthorizationException
     */
    protected function authorizationFailAction()
    {
        //
    }

    /**
     * When user have not authorize to launch a request
     * This is hook the method that can watch them for make an action
     * This method permet to custom fail exception
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
     * Get the current request
     *
     * @return Request
     */
    protected function getRequest()
    {
        return $this->request;
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
     *
     * @return Request
     */
    public function __call($name, array $arguments)
    {
        if (method_exists($this->request, $name)) {
            return call_user_func_array([$this->request, $name], $arguments);
        }

        throw new BadMethodCallException('The method ' . $name . ' does not define.');
    }

    /**
     * __get
     *
     * @param  string $name
     *
     * @return string
     */
    public function __get($name)
    {
        return $this->request->$name;
    }
}
