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

    /**
     * @param string $type
     * @param array $config
     * @throws MailException
     * @return Mail|Smtp
     */
    public static function factory($type, array $config)
    {
        if (!in_array($type, ["smtp", "mail"])) {
            throw new MailException("le type n'est pas réconnu.", E_USER_ERROR);
        }

       if ($type == "mail") {
           if (!self::$instance instanceof Mail) {
               self::$instance = Mail::takeInstance();
           }
       } else {
           if (!self::$instance instanceof Smtp) {
               self::$instance = new Smtp($config);
           }
       }


        return self::$instance;
    }
}