<?php

/**
 * Systeme d'envoye de mail utilisant le fonction
 * mail de php.
 * @author Frank Dakia <dakiafranck@gmail.com>
 * @package System
 */

namespace System;

class Mail
{
	// Liste des entetes
	private $headers = [];
	// definir le desitinataire
	private $to = null;
	// definir l'object du mail
	private $subject = null;
	// definir le message
	private $message = null;
	// permet de compter le nombre content-type
	private $part = 0;
	// definir le type de retoure chariot CRLF ou LF
	private $sep;
	// definir le frontiere entre les contenus.
	private $boundary;
	// Singleton de mail
	private static $mail = null;

	/**
	 * addHeader, Ajout une entête
	 * @param string $head
	 * @param self
	 */
	public function addHeader($key, $value)
	{
		if (array_key_exists($key, $this->headers["top"])) {
			if (!is_array($this->headers["top"][$key])) {
				$old = $this->headers["top"][$key];
				$this->headers["top"][$key] = [$old, $value];
			} else {
				array_push($this->headers["top"][$key], $value);
			}
		} else {
			$this->headers["top"][$key] = $value;
		}
		return $this;
	}

	/**
	 * addFeatureHeader, permet d'ajout une entete
	 * @param string $key
	 * @param string $value
	 * @return self
	 */
	private function addFeatureHeader($key, $value)
	{
		if (strtolower($key) == "content-type") {
			$this->headers["bottom"][$this->part] = [];
			$this->part++;
		}

		if ($key == "data") {
			$value = preg_replace("@\n$@", "", $value);
			$data = $this->sep . $this->sep. $value;
		} else {
			$data = "$key: $value";
		}

		if (($this->part - 1) === -1) {
			array_push($this->headers["bottom"][$this->part], $data);
		} else {
			array_push($this->headers["bottom"][$this->part - 1], $data);
		}

		return $this;
	}

	/**
	 * formdtHeader, formateur d'entete smtp
	 * @return string
	 */
	public function formatHeader()
	{
		$content_length = count($this->headers["bottom"]);
		$sep = $this->sep;

		$form = "";

		foreach ($this->headers["top"] as $key => $value) {
			$form .= "$key: $value" . $sep;
		}

		if ($content_length == 1) {
			foreach ($this->headers["bottom"] as $value) {
				$form .= $value . $sep;
			}
		} else {
			$form .= "Content-Type: multipart/mixed; boundary=\"{$this->boundary}\"{$sep}{$sep}";
			$form .= $this->boundary . $sep;
			foreach ($this->headers["bottom"] as $value) {
				foreach ($value as $key => $v) {
					$form .= $v . $sep;
				}
				$form .= $this->boundary . $sep;
			}
		}
		return $form;
	}

	/**
	 * getHeader, retourne les entetes definies.
	 * @return string
	 */
	public function getHeader()
	{
		return (object) $this->headers;
	}

	/**
	 * to, definir le receveur
	 * @param string $to
	 * @param string $to=null
	 * @param Mail
	 */
	public function to($to, $name = null, $smtp = false)
	{
		$to = $this->formatEmail($to, $name);

		if ($smtp === true) {
			$this->addFeatureHeader("To", $to);
		} else {
			if ($this->to !== null) {
				$this->to .= ", ";
			} else {
				$this->to = $to;
			}
		}
		return $this;
	}

	/**
	 * Formaté l'email recu.
	 * @param  string
	 * @param  string
	 * @return array
	 */
	private function formatEmail($email, $name)
	{
		if (!$name && preg_match('#^(.+) +<(.*)>\z#', $email, $matches)) {
			return [$matches[2] => $matches[1]];
		} else {
			return [$email => $name];
		}
	}

	/**
	 * addFile, Permet d'ajout un fichier d'attachement
	 * @param string $file
	 * @param Mail
	 */
	public function addFile($file)
	{
		if (!is_file($file)) {
			trigger_error("Ce n'est pas une fichier.", E_USER_ERROR);
		}
		$content = file_get_contents($file);
		$base_name = basename($file);
		$this->addFeatureHeader("Content-Type", "application/octect-stream; name=\"{$base_name}\"");
		$this->addFeatureHeader("Content-Transfer-Encoding", "base64");
		$this->addFeatureHeader("Content-Disposition", "attachement");
		$this->addFeatureHeader("data", chunk_split(base64_encode($content)));
		return $this;
	}

	/**
	 * subject, Definit le suject du mail
	 * @param string $subject
	 * @param string $smtp=false
	 * @param Mail
	 */
	public function subject($subject, $smtp = false)
	{
		if ($smtp === true) {
			$this->addHeader("Subject", $subject);
		} else {
			$this->subject = $subject;
		}
		return $this;
	}

	/**
	 * from, Definir l'expediteur du mail
	 * @param string $from
	 * @param string $name=null
	 * @return self
	 */
	public function from($from, $name = null, $smtp = false)
	{
		$from = ($name !== null) ? (ucwords($name) . " <{$from}>") : $from;
		if (!isset($this->fromDefined)) {
			$this->addHeader("From", $from);
		} else {
			$this->fromDefined = true;
		}
		return $this;
	}

	/**
	 * toHtml, Definir le type de contenu en text/html
	 * @param string $html=null
	 * @return self
	 */
	public function toHtml($html = null)
	{
		$this->addFeatureHeader("Content-Type", "text/html; charset=utf-8");
		$this->addFeatureHeader("Content-Transfer-Encoding", "8bit");
		if (is_string($html)) $this->addFeatureHeader("data", $html);
		return $this;
	}

	/**
	 * toText, Definir le corps du message
	 * @param string text
	 * @return self
	 */
	public function toText($text = null)
	{
		$this->addFeatureHeader("Content-Type", "text/plain; charset=utf-8");
		$this->addFeatureHeader("Content-Transfer-Encoding", "8bit");
		if (is_string($text)) $this->addFeatureHeader("data", $text);
		return $this;
	}

	/**
	 * Adds blind carbon copy
	 * @param string $mail
	 * @param string $name=null
	 * @return self
	 */
	public function addBcc($mail, $name = null)
	{
		$mail = ($name !== null) ? (ucwords($name) . " <{$mail}>") : $mail;
		$this->addHeader("Bcc", $mail);
	}

	/**
	 * Adds carbon copy
	 * @param string $mail
	 * @param string $name=null
	 * @return self
	 */
	public function addCc($mail, $name = null)
	{
		$mail = ($name !== null) ? (ucwords($name) . " <{$mail}>") : $mail;
		$this->addHeader("Cc", $mail);
		return $this;
	}

	/**
	 * Adds Reply-To
	 * @param string $mail
	 * @param string $name=null
	 * @return self
	 */
	public function addReplyTo($mail, $name = null)
	{
		$mail = ($name !== null) ? (ucwords($name) . " <{$mail}>") : $mail;
		$this->addHeader("Replay-To", $mail);
		return $this;
	}

	/**
	 * Adds Return-Path
	 * @param string $mail
	 * @param string $name=null
	 * @return self
	 */
	public function addReturnPath($mail, $name = null)
	{
		$mail = ($name !== null) ? (ucwords($name) . " <{$mail}>") : $mail;
		$this->addHeader("Return-Path", $mail);
		return $this;
	}

	/**
	 * Sets email priority.
	 * @param  int $priority
	 * @return self
	 */
	public function addPriority($priority)
	{
		$this->addHeader('X-Priority', (int) $priority);
		return $this;
	}

	/**
	 * Message, definir le corps du message
	 * @param string text
	 * @return self
	 */
	public function message($message)
	{
		if (!is_string($message)) {
			throw new \InavlidArgumentException(__METHOD__."() parameter most be string " . gettype($message) . "given", 1);
		}
		$this->message = $message;
		return $this;
	}

	/**
	 * send, Envoie le mail
	 * @param callable|null $cb
	 * @return self
	 */
	public function send($cb = null)
	{
		if (empty($this->to) || empty($this->subject) || empty($this->message)) {
			trigger_error(__METHOD__. "(): an error comming because your don't given the following parameter: SENDER, SUBJECT or MESSAGE.", E_USER_ERROR);
		}

		$status = @mail($this->to, $this->subject, $this->message, $this->formatHeader());

		if (is_callable($cb)) {
			return call_user_func($cb, $status);
		}

		return $status;
	}

	/**
	 * Mise en privé des fonctions magic __clone et __construct
	 */
	private function __clone(){}

	private function __construct()
	{
		if (defined('PHP_EOL')) {
			$this->sep = PHP_EOL;
		} else {
			$this->sep = (strpos(PHP_OS, 'WIN') === false) ? "\n" : "\r\n";
		}
		$this->boundary = "__snoop-Diagnostic.ci-" . md5(date("r", time()));
		$this->headers = ["top" => [], "bottom" => []];
		$this->addHeader("MIME-Version", "1.0");
		$this->addHeader("X-Mailer",  "Snoop Framework");
		$this->addHeader("Date", date("r"));
	}

	/**
	 * load, charger la classe MAil en mode singleton
	 * @return new Mail
	 */
	public static function load()
	{
		if (self::$mail !== null) {
			return self::$mail;
		}
		self::$mail = new self;
		return self::$mail;
	}

}
