<?php
namespace Bow\Mail;

use Bow\Exception\MailException;

/**
 * Class BowMail
 *
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Mail
 */
class BowMail
{
    /**
     * @var SimpleMail|Smtp
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
     * @return SimpleMail|Smtp
     */
    public static function confirgure(\StdClass $config)
    {
        if (!in_array($config->driver, ["smtp", "mail"])) {
            throw new MailException("Le type n'est pas réconnu.", E_USER_ERROR);
        }

        if ($config->driver == "mail") {
            if (!self::$instance instanceof SimpleMail) {
               self::$instance = new SimpleMail($config->mail);
            }
        } else {
            if (!self::$instance instanceof Smtp) {
                self::$instance = new Smtp($config->smtp);
            }
        }

        return self::$instance;
    }
}