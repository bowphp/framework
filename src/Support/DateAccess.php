<?php
/**
 * Created by PhpStorm.
 * User: papac
 * Date: 5/7/16
 * Time: 9:50 PM
 */

namespace Bow\Support;


class DateAccess
{
    /**
     * @var string|int
     */
    private $date;

    /**
     * @param null|int|string $time
     */
    public function __construct($time = null)
    {
        if ($time === null) {
            $this->date = time();
        } else if (is_string($time)) {
            $this->date = strtotime($time);
        } else {
            $this->date = $time;
        }
    }

    /**
     * @return bool|string
     */
    public function getYear()
    {
        return date("y", $this->date);
    }

    /**
     * @return bool|string
     */
    public function getFullYear()
    {
        return date("Y", $this->date);
    }

    /**
     * @return bool|string
     */
    public function getDay()
    {
        return date("d", $this->date);
    }

    /**
     * @return bool|string
     */
    public function getDate()
    {
        return date("N", $this->date);
    }

    /**
     * @return bool|string
     */
    public function getHours()
    {
        return date("H", $this->date);
    }

    /**
     * @return bool|string
     */
    public function getSecondes()
    {
        return date("s", $this->date);
    }

    public function getMinutes()
    {
        return date("i", $this->date);
    }

    /**
     * @return bool|string
     */
    public function getMonth()
    {
        return date("m", $this->date);
    }

    /**
     * @return bool|string
     */
    public function getTimes()
    {
        return date("T", $this->date);
    }

    /**
     * @return bool|string
     */
    public function toISODate()
    {
        return date(DATE_ISO8601, $this->date);
    }

    /**
     * @return bool|string
     */
    public function toUTCDate()
    {
        return date("M", $this->date);
    }

    /**
     * @return bool|string
     */
    public function toATOMDate()
    {
        return date(DATE_ATOM, $this->date);
    }

    /**
     * @return bool|string
     */
    public function toTime()
    {
        return date("N", $this->date);
    }
}