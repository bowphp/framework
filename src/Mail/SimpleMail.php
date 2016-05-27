<?php
namespace Bow\Mail;

use Bow\Support\Str;
use Bow\Support\Util;
use InvalidArgumentException;
use Bow\Exception\MailException;

/**
 * Systeme d'envoye de mail utilisant le fonction mail de php.
 *
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Mail
 */
class SimpleMail extends Message
{
	/**
	 * @var array
	 */
	private $config;
	/**
	 * send, Envoie le mail
	 * 
	 * @param callable|null $cb
	 * @throws InvalidArgumentException
	 * @throws MailException
	 * @return self
	 */
	public function send($cb = null)
	{
		if (empty($this->to) || empty($this->subject) || empty($this->message)) {
			throw new InvalidArgumentException("Une erreur est survenu. L'expediteur ou le message ou l'object omit.", E_USER_ERROR);
		}

		if (count($this->config) > 0) {

			if (!$this->fromDefined) {
				$form = $this->config[0];
			} else if (!Str::isMail(explode(" ", $this->from)[0])) {
				$form = $this->config[$this->from];
			} else {
				throw new MailException("L'expediteur n'est spécifié.", E_USER_ERROR);
			}

			$this->from($form["address"], $form["username"]);
		}


		$status = @mb_send_mail($this->to, $this->subject, $this->message, $this->makeSendData());

        if ($cb) {
            Util::launchCallback($cb, $status);
        }

		return $status;
	}

	/**
	 * Mise en privé des fonctions magic __clone
	 */
	private function __clone()
	{
	}

    /**
     * Construction d'une instance de SimpleMail
	 *
	 * @param array $config
     */
	public function __construct(array $config = [])
	{
		$this->config = $config;
		$this->boundary = "__Bow-Framework-" . md5(date("r"));
	}
}
