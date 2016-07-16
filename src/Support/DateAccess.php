<?php
namespace Bow\Support;

/**
 * Class DateAccess
 *
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Support
 */
class DateAccess
{
    /**
     * @var string|int
     */
    private $date;

    /**
     * Constructeur d'instance.
     *
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
     * Retourne l'année eg 16
     *
     * @return bool|string
     */
    public function getYear()
    {
        return date("y", $this->date);
    }

    /**
     * Retourne l'année total eg 2016
     *
     * @return bool|string
     */
    public function getFullYear()
    {
        return date("Y", $this->date);
    }

    /**
     * Retourne le jour
     *
     * @return bool|string
     */
    public function getDay()
    {
        return date("d", $this->date);
    }

    /**
     * Retourne la date du jour
     *
     * @return bool|string
     */
    public function getDate()
    {
        return date("N", $this->date);
    }

    /**
     * Retourne les heures
     *
     * @return bool|string
     */
    public function getHours()
    {
        return date("H", $this->date);
    }

    /**
     * Retourne les seconds
     *
     * @return bool|string
     */
    public function getSecondes()
    {
        return date("s", $this->date);
    }

    /**
     * Retourne les minutes
     *
     * @return bool|string
     */
    public function getMinutes()
    {
        return date("i", $this->date);
    }

    /**
     * Retourne le mois
     *
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
     * La date en format ISO
     *
     * @return bool|string
     */
    public function toISODate()
    {
        return date(DATE_ISO8601, $this->date);
    }

    /**
     * La date en format UTC
     *
     * @return bool|string
     */
    public function toUTCDate()
    {
        return date("M", $this->date);
    }

    /**
     * La date en format ATOM
     *
     * @return bool|string
     */
    public function toATOMDate()
    {
        return date(DATE_ATOM, $this->date);
    }

    /**
     * Retourne la version timestamp
     *
     * @return bool|string
     */
    public function toTime()
    {
        return date("N", $this->date);
    }

    /**
     * Vérifie si la date est dans le future
     *
     * @return bool
     */
    public function isFuture()
    {
        return time() < $this->date;
    }

    /**
     * @return bool|string
     */
    public function __toString()
    {
        return date("Y-m-d H:i:s", $this->date);
    }

    /**
     * @param string $diffDate
     * @return \DateInterval
     */
    public function diference($diffDate)
    {
        return date_diff(new \DateTime($diffDate), new \DateTime($this->date));
    }

    public function add($format)
    {

    }
}