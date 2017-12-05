<?php
namespace Bow\Validation;

use AuthorizationException;
use Bow\Http\Request;
use BadMethodCallException;
use Bow\Validation\Exception\ValidationException;

abstract class ValidationRequest
{
    /**
     * @var Validate
     */
    private $validate;

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
     *
     * @return mixed
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

        $this->request = new Request();
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
     * @return array
     */
    protected function rules()
    {
        return [
            // Vos régles
        ];
    }

    /**
     * @return array
     */
    protected function keys()
    {
        return [
            '*'
        ];
    }

    /**
     * @return bool
     */
    protected function authorize()
    {
        return true;
    }

    /**
     * Quand l'utilisateur n'a pas l'authorization de lance cette requête
     * C'est la methode qui est lancer pour bloquer l'utilisateur
     */
    protected function authorizationFailAction()
    {
        //throw new AuthorizationException('Vous n\'avez l\'authorisation pour faire requête');
    }

    /**
     * @throws AuthorizationException
     */
    protected function sendFailAuthorization()
    {
        throw new AuthorizationException('Vous n\'avez l\'authorisation pour faire requête');
    }

    /**
     * @throws AuthorizationException
     */
    protected function sendFailValidation()
    {
        throw new ValidationException('Erreur de validation');
    }

    /**
     * Quand l'utilisateur n'a pas l'authorization de lance cette requête
     * C'est la methode qui est lancer pour bloquer l'utilisateur
     */
    protected function validationFailAction()
    {
        //
    }

    /**
     * Permet de verifier si la réquete
     */
    protected function fails()
    {
        return $this->validate->fails();
    }

    /**
     * Permet de récupérer le validateur
     *
     * @return Validate
     */
    protected function getValidationInstance()
    {
        return $this->validate;
    }

    /**
     * Permet de récupérer le message du de la dernier erreur
     *
     * @return string
     */
    protected function getMessage()
    {
        return $this->validate->getLastMessage();
    }

    /**
     * Permet de récupérer tout les messages d'erreur
     *
     * @return array
     */
    protected function getMessages()
    {
        return $this->validate->getMessages();
    }

    /**
     * Permet de récupérer les données de la validation
     *
     * @return array
     */
    protected function getValidationData()
    {
        return $this->data;
    }

    /**
     * Permet de lancer une exception
     *
     * @throws \Bow\Validation\Exception\ValidationException;
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

        throw new BadMethodCallException('La methode '. $name.' n\'est pas défini.');
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
