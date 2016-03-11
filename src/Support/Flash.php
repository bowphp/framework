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

    public function warning($value = null)
    {
        if ($value !== null) {
            $this->warning = $value;
        }

        return $this->warning;
    }

    public function information($value = null)
    {
        if ($value !== null) {
            $this->information = $value;
        }

        return $this->information;
    }

    /**
     * @param $value
     * @return array
     */
    public function error($value = null)
    {
        if ($value !== null) {
            $this->error = $value;
        }

        return $this->error;
    }

    /**
     * @param $value
     * @return array
     */
    public function success($value = null)
    {
        if ($value !== null) {
            $this->success = $value;
        }

        return $this->success;
    }
}