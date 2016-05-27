<?php
namespace Bow\Support\Validate;

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
    private $message = null;

    /**
     * @var array
     */
    private $corruptesFields;

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
        $this->message = $message;
        $this->corruptesFields = $corruptesFields;
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
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }
}