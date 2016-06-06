<?php
namespace Bow\Mail;

use Bow\Exception\MailException;
use Bow\Http\Response;

/**
 * Class BowMail
 *
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Mail
 */
class Mail
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

    /**
     * Envoye de mail.
     *
     * @param string $view Le nom de la vue
     * @param array $bind  Les données à passer à la vue.
     * @param callable $cb La fonction
     * @return bool
     *
     * @throws \Bow\Exception\ResponseException
     * @throws \Bow\Exception\ViewException
     */
    public static function send($view, $bind, $cb)
    {
        $type = "text/html";
        $data = "";

        if (is_callable($bind)) {
            $cb = $bind;
            $bind = [];
        }

        $message = new Message();
        call_user_func_array($cb, [$message]);

        ob_start();
        Response::takeInstance()->view($view, $bind);
        $data = ob_get_clean();

        $message->setMessage($data);

        return @mb_send_mail($message->getTo(), $message->getSubject(), $message->getMessage(), $message->compileHeaders());
    }

    /**
     * @return SimpleMail|Smtp
     */
    public static function raw()
    {
        return static::$instance;
    }
}