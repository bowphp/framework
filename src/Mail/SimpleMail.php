<?php
namespace Bow\Mail;

use Bow\Support\Str;
use InvalidArgumentException;
use Bow\Mail\Exception\MailException;

/**
 * Systeme d'envoye de mail utilisant le fonction mail de php.
 *
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Mail
 */
class SimpleMail implements Send
{
    /**
     * @var array
     */
    private $config;
    /**
     * send, Envoie le mail
     *
     * @param Message $message
     * @throws InvalidArgumentException
     * @throws MailException
     * @return bool
     */
    public function send(Message $message)
    {
        if (empty($message->getTo()) || empty($message->getSubject()) || empty($message->getMessage())) {
            throw new InvalidArgumentException("Une erreur est survenu. L'expediteur ou le message ou l'object omit.", E_USER_ERROR);
        }

        if (isset($this->config['mail'])) {

            $section = $this->config['mail']['default'];

            if (!$message->fromIsDefined()) {
                $form = $this->config['mail'][$section];
                $message->from($form["address"], $form["username"]);
            } else {
                if (!Str::isMail($message->getFrom())) {
                    $form = $this->config['mail'][$message->getFrom()];
                    $message->from($form["address"], $form["username"]);
                }
            }
        }

        $to = '';
        $message->setDefaultHeader();

        foreach($message->getTo() as $value) {
            if ($value[0] !== null) {
                $to .= $value[0] . ' <' . $value[1] . '>';
            } else {
                $to .= '<' . $value[1] . '>';
            }
        }

        $status = @mb_send_mail($to, $message->getSubject(), $message->getMessage(), $message->compileHeaders());

        return (bool) $status;
    }

    /**
     * Mise en privé des fonctions magic __clone
     */
    private function __clone() { }

    /**
     * Construction d'une instance de SimpleMail
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }
}
