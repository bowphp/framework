<?php
namespace Bow\Mail;

use Bow\Support\Str;
use InvalidArgumentException;
use Bow\Exception\MailException;

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
	 * @return self
	 */
	public function send(Message $message)
	{
		if (empty($message->getTo()) || empty($message->getSubject()) || empty($message->getMessage())) {
			throw new InvalidArgumentException("Une erreur est survenu. L'expediteur ou le message ou l'object omit.", E_USER_ERROR);
		}

		if (count($this->config) > 0) {

			if (!$message->fromIsDefined()) {
				$form = $this->config[0];
			} else if (!Str::isMail(explode(" ", $message->getFrom())[0])) {
				$form = $this->config[$message->getFrom()];
			} else {
				throw new MailException("L'expediteur n'est spécifié.", E_USER_ERROR);
			}

			$message->from($form["address"], $form["username"]);
		}

		$status = @mb_send_mail(implode(", ", $message->getTo()), $message->getSubject(), $message->getMessage(), $message->compileHeaders());

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
	}
}
