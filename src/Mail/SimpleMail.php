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
class SimpleMail extends Message implements Send
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
		if (empty($this->getTo()) || empty($this->getSubject()) || empty($this->getMessage())) {
			throw new InvalidArgumentException("Une erreur est survenu. L'expediteur ou le message ou l'object omit.", E_USER_ERROR);
		}

		if (count($this->config) > 0) {

			if (!$this->fromIsDefined()) {
				$form = $this->config[0];
			} else if (!Str::isMail(explode(" ", $this->getFrom())[0])) {
				$form = $this->config[$this->getFrom()];
			} else {
				throw new MailException("L'expediteur n'est spécifié.", E_USER_ERROR);
			}

			$this->from($form["address"], $form["username"]);
		}

		$this->setDefaultHeader();

		$status = @mb_send_mail(implode(", ", $this->getTo()), $this->getSubject(), $this->getMessage(), $this->compileHeaders());

        if (is_callable($cb)) {
            call_user_func_array($cb, [$status]);
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
		parent::__construct();
	}
}
