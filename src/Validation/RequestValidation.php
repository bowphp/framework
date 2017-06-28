<?php
namespace Bow\Validation;

use Bow\Http\Request;
use Bow\Http\UploadFile;
use Bow\Resource\Storage;
use BadMethodCallException;
use Psy\Exception\ErrorException;

abstract class RequestValidation extends Validator
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
     * @var UploadFile
     */
    private $file;

    /**
     * @var bool
     */
    private $upload_started = false;

    /**
     * @var Request
     */
    private $request;

    /**
     * TodoValidation constructor.
     */
    public function __construct()
    {
        $this->request = new Request();

        if ((count($this->keys) == 1 && $this->keys[0] === '*') || count($this->keys) == 0) {
            $this->data = $this->request->input()->all();
        } else {
            $this->data = $this->request->input()->excepts($this->keys);
        }

        $this->validate = Validator::make($this->data, $this->rules);
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

    /**
     * Permet de démmarrer le système upload
     *
     * @param $key
     */
    public function startUploadFor($key)
    {
        if (!$this->upload_started) {
            $this->upload_started = true;
            $this->file = Request::file($key);
        }
    }

    /**
     * @return bool
     */
    public function hasFile()
    {
        return is_null($this->file) ? false : $this->file->isUploaded() && $this->file->isValid();
    }

    /**
     * Permet de faire upload de fichier
     *
     * @param string $dirname
     * @param string $filename
     * @param bool $overidre_extension
     * @throws ErrorException
     * @return string
     */
    public function makeUpload($dirname, $filename, $overidre_extension = false)
    {
        if (!$this->upload_started) {
            throw new ErrorException('Lancez la methode "startUploadFile" avant');
        }

        if ($overidre_extension) {
            $filename = $filename.'.'.$this->file->getExtension();
        }

        $this->file->move(Storage::resolvePath($dirname), $filename);
        return '/'.ltrim(rtrim($dirname, '/'), '/').'/'.ltrim($filename, '/');
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