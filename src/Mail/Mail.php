<?php
namespace Bow\Mail;

use Bow\Exception\MailException;
use Bow\Http\Response;

/**
 * Class Mail
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
     * Configure la classe Mail
     *
     * @param \StdClass $config La configuration
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
     * @param array|callable $bind Les données à passer à la vue.
     * @param callable $cb [optional] La fonction
     * @return bool
     *
     * @throws \Bow\Exception\ResponseException
     * @throws \Bow\Exception\ViewException
     */
    public static function send($view, $bind, $cb = null)
    {
        if (is_callable($bind)) {
            $cb = $bind;
            $bind = [];
        }

        $message = new Message();
        call_user_func_array($cb, [$message]);

        ob_start();
        Response::takeInstance()->view($view, $bind. null);
        $data = ob_get_clean();

        $message->setMessage($data);
        return self::$instance->send($message);
    }

    /**
     * Envoye de mail simulaire a la fonction mail de PHP
     *
     * @param string $to Le destinateur
     * @param string $subject L'objet du mail
     * @param string $data Le message du meail
     * @param array  $headers [optinal] Les entêtes additionnel du mail.
     * @return SimpleMail|Smtp
     */
    public static function raw($to, $subject, $data, array $headers = [])
    {
        $message = new Message();

        $message->to($to)->subject($subject)->setMessage($data);
        foreach($headers as $key => $value) {
            $message->addHeader($key, $value);
        }

        return static::$instance->send($message);
    }
}