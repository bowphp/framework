<?php
/**
 * @author Franck Dakia <dakiafranck@gmail.com>
 */

namespace Bow\Mail;
use Bow\Exception\MailException;

class BowMail
{
    /**
     * @var Mail|Smtp
     */
    private static $instance;

    private function __construct()
    {
    }

    private function __clone()
    {
    }

    /**
     * @param \StdClass $config
     * @throws MailException
     * @return Mail|Smtp
     */
    public static function confirgure(\StdClass $config)
    {
        if (!in_array($config->driver, ["smtp", "mail"])) {
            throw new MailException("Le type n'est pas réconnu.", E_USER_ERROR);
        }

        if ($config->driver == "mail") {
            if (!self::$instance instanceof Mail) {
               self::$instance = new Mail($config->mail);
            }
        } else {
            if (!self::$instance instanceof Smtp) {
                self::$instance = new Smtp($config->smpt);
            }
        }

        return self::$instance;
    }
}