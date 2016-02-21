<?php

/**
 * Systeme d'envoye de mail utilisant le fonction mail de php.
 * 
 * @author Frank Dakia <dakiafranck@gmail.com>
 * 
 * @package Bow
 */

namespace Bow\Mail;


use Bow\Support\Util;
use InvalidArgumentException;


class Mail extends Message
{

	/**
	 * send, Envoie le mail
	 * 
	 * @param callable|null $cb
	 * @throws InvalidArgumentException
	 * @return self
	 */
	public function send($cb = null)
	{
		if (empty($this->to) || empty($this->subject) || empty($this->message)) {
			throw new InvalidArgumentException(__METHOD__. "(): an error comming because your don't given the following parameter: SENDER, SUBJECT or MESSAGE.", E_USER_ERROR);
		}
		$status = mail(implode(Util::sep(), $this->to), $this->subject, $this->message, $this->formatHeader());

		Util::launchCallback($cb, $status);

		return $status;
	}

	/**
	 * Mise en privé des fonctions magic __clone et __construct
	 */
	private function __clone(){}

	private function __construct()
	{
		$this->boundary = "__Bow-Framework-" . md5(date("r"));
		$this->addHeader("MIME-Version", "1.0");
		$this->addHeader("X-Mailer",  "Bow Framework");
		$this->addHeader("Date", date("r"));
	}

	/**
	 * takeInstance, charge la classe Mail en mode singléton
	 * 
	 * @return self
	 */
	public static function takeInstance()
	{
		if (self::$mail !== null) {
			return self::$mail;
		}

		self::$mail = new self;
		
		return self::$mail;
	}
}
