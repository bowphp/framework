<?php
namespace Bow\Support;

use \StdClass;

/**
 * Class Flash
 *
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Support
 */
class Flash
{
    /**
     * @var array
     */
    private $warning     = [];
    /**
     * @var array
     */
    private $information = [];
    /**
     * @var array
     */
    private $error       = [];
    /**
     * @var array
     */
    private $success     = [];

    /**
     * warning
     *
     * @param null|string $value nouvelle valeur du message de warning
     *
     * @return StdClass|null
     */
    public function warning($value = null)
    {
        if ($value !== null) {
            $this->warning = $value;
        } else {
            return (object) ["level" => "warning", "message" => $this->warning];
        }
    }

    /**
     * danger
     *
     * @param null $value nouvelle valeur du message de danger => warning
     *
     * @return StdClass|null
     */
    public function danger($value = null)
    {
        if ($value !== null) {
            $this->warning = $value;
        } else {
            return (object) ["level" => "danger", "message" => $this->warning];
        }
    }

    /**
     * information
     *
     * @param null $value nouvelle valeur du message de information
     *
     * @return StdClass|null
     */
    public function information($value = null)
    {
        if ($value !== null) {
            $this->information = $value;
        } else {
            return (object) ["level" => "info", "message" => $this->information];
        }
    }

    /**
     * error
     *
     * @param null|string $value nouvelle valeur du message de error
     *
     * @return StdClass|void
     */
    public function error($value = null)
    {
        if ($value !== null) {
            $this->error = $value;
        } else {
            return (object) ["level" => "error", "message" => $this->error];
        }
    }

    /**
     * success
     *
     * @param null|string $value nouvelle valeur du message de success
     *
     * @return StdClass
     */
    public function success($value = null)
    {
        if ($value !== null) {
            $this->success = $value;
        } else {
            return (object) ["level" => "success", "message" => $this->success];
        }
    }

    /**
     * __sleep
     *
     * @return StdClass
     */
    public function __sleep()
    {
        return ["error", "information", "success", "warning"];
    }
}