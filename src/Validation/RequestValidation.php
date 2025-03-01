<?php

declare(strict_types=1);

namespace Bow\Validation;

use BadMethodCallException;
use Bow\Http\Request;
use Bow\Validation\Exception\AuthorizationException;
use Bow\Validation\Exception\ValidationException;

abstract class RequestValidation
{
    /**
     * The Validate instance
     *
     * @var Validate
     */
    private Validate $validate;

    /**
     * The request data
     *
     * @var array
     */
    private array $data;

    /**
     * The Request instance
     *
     * @var Request
     */
    private Request $request;

    /**
     * TodoValidation constructor.
     *
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
            $this->data = $this->request->only($keys);
        }

        $this->validate = Validator::make($this->data, $this->rules(), $this->messages());

        if ($this->validate->fails()) {
            $this->validationFailAction();
            $this->validate->throwError();
        }
    }

    /**
     * The define the user authorization level
     *
     * @return bool
     */
    protected function authorize(): bool
    {
        return true;
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
     * Send fails authorization
     *
     * @throws AuthorizationException
     */
    private function sendFailAuthorization()
    {
        throw new AuthorizationException(
            'You do not have permission to make a request'
        );
    }

    /**
     * The allowed validation key
     *
     * @return array
     */
    protected function keys(): array
    {
        return [
            '*'
        ];
    }

    /**
     * The rules list
     *
     * @return array
     */
    protected function rules(): array
    {
        return [
            // Your rules
        ];
    }

    /**
     * The define the user custom message
     *
     * @return array
     */
    protected function messages(): array
    {
        return [];
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
     * When user have not authorized to launch a request
     * This is hook the method that can watch them for make an action
     * This method able to custom fail exception
     *
     * @throws AuthorizationException
     */
    protected function validationFailAction()
    {
        //
    }

    /**
     * Throws an exception
     *
     * @throws ValidationException;
     */
    protected function throwError(): void
    {
        $this->validate->throwError();
    }

    /**
     * __call
     *
     * @param string $name
     * @param array $arguments
     * @return Request
     */
    public function __call(string $name, array $arguments)
    {
        if (method_exists($this->request, $name)) {
            return call_user_func_array([$this->request, $name], $arguments);
        }

        throw new BadMethodCallException(
            'The method ' . $name . ' does not defined.'
        );
    }

    /**
     * __get
     *
     * @param string $name
     * @return string
     */
    public function __get(string $name)
    {
        return $this->request->$name;
    }

    /**
     * Get the validator instance
     *
     * @return Validate
     */
    protected function getValidationInstance(): Validate
    {
        return $this->validate;
    }

    /**
     * Get the message of the last error
     *
     * @return string
     */
    protected function getMessage(): string
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
    protected function getValidationData(): array
    {
        return $this->data;
    }

    /**
     * Get the current request
     *
     * @return Request
     */
    protected function getRequest(): Request
    {
        return $this->request;
    }
}
