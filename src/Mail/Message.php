<?php


namespace Bow\Mail;

use Bow\Support\Util;

abstract class Message
{
    const END = "\r\n";
	/**
	 * Liste des entêtes
	 * @var array
	 */
	protected $headers = [];
	/**
	 * définir le destinataire
	 * @var array
	 */
	protected $to = [];
	/**
	 * définir l'object du mail
	 * @var string
	 */
	protected $subject = null;
	/**
	 * @var array
	 */
	protected $form = [];
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
	 * addHeader, Ajout une entête
	 *
	 * @param string $key
	 * @param string $value
	 * @return self
	 */
	protected function addHeader($key, $value)
	{
        $this->headers[] = ucwords($key) . ": " . $value;
		return $this;
	}

	/**
	 * formatHeader, formateur d'entête SMTP
	 *
	 * @return string
	 */
	protected function formatHeader()
	{
        $headers = "";
        if ($this->form) {
            $headers .= "From: " . $this->form . self::END;
        }

        if ($this->subject) {
            $headers .= "Subject: " . $this->subject . self::END;
        }

        $headers .= implode(self::END, $this->headers);
        return $headers;
	}

	/**
	 * getHeader, retourne les entêtes définies.
	 *
	 * @return string
	 */
	public function getHeader()
	{

	}

	/**
	 * to, définir le récépteur
	 *
	 * @param string $to
	 * @param string $name
	 * 
	 * @return self
	 */
	public function to($to, $name = null)
	{
		$this->to[] = $this->formatEmail($to, $name);

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
	private function formatEmail($email, $name = "")
	{
        /**
         * Organisation de la liste des senders
         */
		if (!$name && preg_match('#^(.+) +<(.*)>\z#', $email, $matches)) {
            array_shift($matches);
			return [$matches[0] , $matches[1]];
		} else {
			return [$name, $email];
		}
	}

	protected function getTo()
	{
		$to = "";
        $i = 0;

		foreach($this->to as $value) {
            if ($i > 0) {
                $to .= ", ";
            }
            $i++;
			if ($value[0] !== null) {
                $to .= "{$value[0]} <{$value[1]}>";
			} else {
                $to .= "{$value[1]}";
            }
		}

        return $to;
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
		// récupération du contenu du fichier
		$content = file_get_contents($file);
        // récupération du nom de fichier.
		$base_name = basename($file);
        $this->headers[] = $this->boundary;
		$this->headers[] = "Content-Type: application/octect-stream; name=\"{$base_name}\"";
		$this->headers[] = "Content-Transfer-Encoding: base64";
        $this->headers[] = "Content-Disposition: attachement; filename=\"$base_name\"" . self::END;
        $this->headers[] = chunk_split(base64_encode($content));
		
		return $this;
	}

	/**
	 * subject, Définit le suject du mail
	 *
	 * @param string $subject
	 * 
	 * @return self
	 */
	public function subject($subject)
	{
        $this->subject = $subject;
		return $this;
	}

	/**
	 * from, définir l'expéditeur du mail
	 *
	 * @param string $from
	 * @param string $name=null
	 * 
	 * @return self
	 */
	public function from($from, $name = null)
	{
		$from = ($name !== null) ? (ucwords($name) . " <{$from}>") : $from;

        if ($this->fromDefined === false) {
            $this->form = $from;
            $this->fromDefined = true;
        }

		return $this;
	}

	/**
	 * toHtml, définir le type de contenu en text/html
     *
	 * @param string $html=null
	 * @return self
	 */
	public function html($html)
	{
        return $this->type($html, "text/html");
	}

	/**
	 * toText, définir le corps du message
	 * 
	 * @param string $text
	 * 
	 * @return self
	 */
	public function text($text = null)
	{
		return $this->type($text, "text/plain");
	}

    /**
     * @param $message
     * @param $type
     * @return $this
     */
    private function type($message, $type)
    {
        $this->headers[] = $this->boundary;
        $this->headers[] = "Content-Type: $type; charset=utf-8" . self::END;
        $this->headers[] = $message . self::END;
        $this->headers[] = $this->boundary;

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
	 * @param string $contentType
	 * @throws \InvalidArgumentException
	 * @return self
	 */
	public function message($message, $contentType = "text")
	{
		if (!is_string($message)) {
			throw new \InvalidArgumentException("Parameter most be string " . gettype($message) . "given", 1);
		}

		$this->message = $message;
        $this->type($message, "text/" . ($contentType == "text" ? "plain" : "html"));
		
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