<?php


namespace Bow\Mail;

use Bow\Support\Util;

abstract class Message
{

	/**
	 * Liste des entêtes
	 *
	 * @var array
	 */
	protected $headers = ["top" => [], "bottom" => []];
	/**
	 * définir le destinataire
	 * @var string
	 */
	protected $to = null;
	/**
	 * définir l'object du mail
	 * @var string
	 */
	protected $subject = null;
	/**
	 * @var string
	 */
	protected $form = null;
	/**
	 * définir le message
	 * @var string
	 */
	protected $message = null;
	/**
	 * permet de compter le nombre content-type
	 *
	 * @var int
	 */
	private $part = 0;
	/**
	 * définir le frontière entre les contenus.
	 *
	 * @var string
	 */
	protected $boundary;
	/**
	 * Singleton de mail
	 *
	 * @var self
	 */
	protected static $mail = null;
	/**
	 * fromDefined
	 *
	 * @var boolean
	 */
	protected $fromDefined = false;
    /**
     * @var string
     */
    protected $sep;

	/**
	 * addHeader, Ajout une entête
	 *
	 * @param string $key
	 * @param string $value
	 * @return self
	 */
	public function addHeader($key, $value)
	{
        $top = $this->headers["top"];

		if (array_key_exists($key, $top)) {
			if (!is_array($top[$key])) {
				$old = $top[$key];
                $top[$key] = [$old, $value];
			} else {
				array_push($top[$key], $value);
			}
		} else {
            $top[$key] = $value;
		}
		
		return $this;
	}

	/**
	 * addFeatureHeader, permet d'ajout une entête
	 *
	 * @param string $key
	 * @param string $value
	 * 
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
	 * formatHeader, formateur d'entête SMTP
	 *
	 * @return string
	 */
	public function formatHeader()
	{

		$content_length = count($this->headers["bottom"]);
		$sep = Util::sep();

		$form = "";

		foreach ($this->headers["top"] as $key => $value) {
			$form .= "$key: $value" . $sep;
		}

		if ($this->subject) {
			$form .= "subject: " . $this->subject . $sep;
		}

		if ($content_length == 1) {
			foreach ($this->headers["bottom"] as $value) {
				foreach ($value as $v) {
					$form .= $v . $sep;
				}
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
	 * getHeader, retourne les entêtes définies.
	 *
	 * @return string
	 */
	public function getHeader()
	{
		return (object) $this->headers;
	}

	/**
	 * to, définir le récépteur
	 *
	 * @param string $to
	 * @param string $name
	 * @param bool $smtp
	 * 
	 * @return self
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
	 * Formaté l'email récu.
	 *
	 * @param  string $email
	 * @param  string $name
	 * 
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
	 *
	 * @param string $file
	 * 
	 * @return self
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
	 * subject, Définit le suject du mail
	 *
	 * @param string $subject
	 * @param bool $smtp
	 * 
	 * @return self
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
	 * from, définir l'expéditeur du mail
	 *
	 * @param string $from
	 * @param string $name=null
	 * @param bool $smtp
	 * 
	 * @return self
	 */
	public function from($from, $name = null, $smtp = false)
	{
		$from = ($name !== null) ? (ucwords($name) . " <{$from}>") : $from;

		if ($smtp === true) {
			$this->form = $from;
		} else {
			if ($this->fromDefined === false) {
				$this->addHeader("From", $from);
			} else {
				$this->fromDefined = true;
			}
		}
		
		return $this;
	}

	/**
	 * toHtml, définir le type de contenu en text/html
     *
	 * @param string $html=null
	 * @return self
	 */
	public function toHtml($html = null)
	{
		$this->addFeatureHeader("Content-Type", "text/html; charset=utf-8");
		$this->addFeatureHeader("Content-Transfer-Encoding", "8bit");

		if (is_string($html)) {
			$this->addFeatureHeader("data", $html);
		}
		
		return $this;
	}

	/**
	 * toText, définir le corps du message
	 * 
	 * @param string $text
	 * 
	 * @return self
	 */
	public function toText($text = null)
	{
		$this->addFeatureHeader("Content-Type", "text/plain; charset=utf-8");
		$this->addFeatureHeader("Content-Transfer-Encoding", "8bit");

		if (is_string($text)) {
			$this->addFeatureHeader("data", $text);
		}
		
		return $this;
	}

	/**
	 * Adds blind carbon copy
	 * 
	 * @param string $mail
	 * @param string $name=null
	 * 
	 * @return self
	 */
	public function addBcc($mail, $name = null)
	{
		$mail = ($name !== null) ? (ucwords($name) . " <{$mail}>") : $mail;
		$this->addHeader("Bcc", $mail);

		return $this;
	}

	/**
	 * Adds carbon copy
	 * 
	 * @param string $mail
	 * @param string $name=null
	 * 
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
	 * 
	 * @param string $mail
	 * @param string $name=null
	 * 
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
	 * 
	 * @param string $mail
	 * @param string $name=null
	 * 
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
	 * 
	 * @param  int $priority
	 * 
	 * @return self
	 */
	public function addPriority($priority)
	{
		$this->addHeader('X-Priority', (int) $priority);
		
		return $this;
	}

	/**
	 * Message, définir le corps du message
	 * @param string $message
	 * @throws \InvalidArgumentException
	 * @return self
	 */
	public function message($message)
	{
		if (!is_string($message)) {
			throw new \InvalidArgumentException(__METHOD__."() parameter most be string " . gettype($message) . "given", 1);
		}

		$this->message = $message;
		
		return $this;
	}

    /**
     * send, envoie de mail.
     *
     * @param callable|null $cb
     * @return mixed
     */
    abstract public function send($cb = null);
}