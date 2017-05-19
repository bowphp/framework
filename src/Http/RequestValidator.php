<?php
namespace Bow\Http;

use Bow\Validation\Validate;
use Bow\Validation\Validator;

abstract class RequestValidator extends Validator
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
     * @var array
     */
    private $data;

    /**
     * @var Validate
     */
    protected $validate;

    /**
     * TodoValidation constructor.
     */
    public function __construct()
    {
        $input = new Input();

        if (count($this->keys) == 1 && $this->keys[0] === '*') {
            $data = $input->all();
        } else {
            $data = $input->excepts($this->keys);
        }

        $this->validate = Validator::make($data, $this->rules);
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
     * @throws \Bow\Exception\ValidationException;
     */
    public function throwError()
    {
        $this->validate->throwError();
    }
}