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
     * @param null $value
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
     * @param null $value
     * @return array|null
     */
    public function danger($value = null)
    {
        if ($value !== null) {
            $this->warning = $value;
        } else {
            return ["level" => "danger", "message" => $this->warning];
        }
    }

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
     * @param $value
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
     * @param $value
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
     * @return array
     */
    public function __sleep()
    {
        return ["error", "information", "success", "warning"];
    }
}