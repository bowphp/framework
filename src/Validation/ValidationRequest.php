<?php
namespace Bow\Validation;

use Bow\Http\Request;
use BadMethodCallException;

abstract class ValidationRequest
{
    /**
     * Règle
     *
     * @var array
     */
    protected $rules = [];

    /**
     * @var array
     */
    protected $keys = ['*'];

    /**
     * @var Validate
     */
    protected $validate;

    /**
     * @var array
     */
    private $data;

    /**
     * @var Request
     */
    private $request;

    /**
     * TodoValidation constructor.
     */
    public function __construct()
    {
        if (!$this->authorized()) {
            $this->callAuthorizationFailAction();
        }

        $this->request = new Request();

        if ((count($this->keys) == 1 && $this->keys[0] === '*') || count($this->keys) == 0) {
            $this->data = $this->request->input()->all();
        } else {
            $this->data = $this->request->input()->excepts($this->keys);
        }

        $this->validate = Validator::make($this->data, $this->rules);
    }

    /**
     * @return bool
     */
    public function authorized()
    {
        return true;
    }

    public function callAuthorizationFailAction()
    {
        abort(500);
    }

    /**
     * Permet de verifier si la réquete
     */
    public function fails()
    {
        return $this->validate->fails();
    }

    /**
     * Permet de récupérer le validateur
     *
     * @return Validate
     */
    public function getValidation()
    {
        return $this->validate;
    }

    /**
     * Permet de récupérer le message du de la dernier erreur
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->validate->getLastMessage();
    }

    /**
     * Permet de récupérer tout les messages d'erreur
     *
     * @return array
     */
    public function getMessages()
    {
        return $this->validate->getMessages();
    }

    /**
     * Permet de récupérer les données de la validation
     *
     * @return array
     */
    public function getValidationData()
    {
        return $this->data;
    }

    /**
     * Permet de lancer une exception
     *
     * @throws \Bow\Validation\Exception\ValidationException;
     */
    public function throwError()
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
    public function __call($name, array $arguments)
    {
        if (method_exists($this->request, $name)) {
            return call_user_func_array([$this->request, $name], $arguments);
        }

        throw new BadMethodCallException('La methode '. $name.' n\'est pas défini.');
    }

    /**
     * __get
     *
     * @param string $name
     * @return string
     */
    public function __get($name)
    {
        return $this->request->$name;
    }
}