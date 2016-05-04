<?php
/**
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Support
 */

namespace Bow\Support;

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

    public function __construct()
    {
    }

    public function __clone()
    {
    }

    /**
     * warning
     *
     * @param null|string $value nouvelle valeur du message de warning
     *
     * @return array|null
     */
    public function warning($value = null)
    {
        if ($value !== null) {
            $this->warning = $value;
        } else {
            return ["level" => "warning", "message" => $this->warning];
        }
    }

    /**
     * danger
     *
     * @param null $value nouvelle valeur du message de danger => warning
     *
     * @return array|null
     */
    public function danger($value = null)
    {
        if ($value !== null) {
            $this->warning = $value;
        } else {
            return ["level" => "danger", "message" => $this->warning];
        }

        return null;
    }

    /**
     * information
     *
     * @param null $value nouvelle valeur du message de information
     *
     * @return array|null
     */
    public function information($value = null)
    {
        if ($value !== null) {
            $this->information = $value;
        } else {
            return ["level" => "info", "message" => $this->information];
        }

        return null;
    }

    /**
     * error
     *
     * @param null|string $value nouvelle valeur du message de error
     *
     * @return array
     */
    public function error($value = null)
    {
        if ($value !== null) {
            $this->error = $value;
        } else {
            return ["level" => "error", "message" => $this->error];
        }

        return null;
    }

    /**
     * success
     *
     * @param null|string $value nouvelle valeur du message de success
     *
     * @return array
     */
    public function success($value = null)
    {
        if ($value !== null) {
            $this->success = $value;
        } else {
            return ["level" => "success", "message" => $this->success];
        }

        return null;
    }

    /**
     * __sleep
     *
     * @return array
     */
    public function __sleep()
    {
        return ["error", "information", "success", "warning"];
    }
}