<?php


namespace Bow\Mail;

use Bow\Exception\MailException;
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
     * @var array
     */

    protected $additonnalHeader = [];

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
    protected $attachement = [];

	/**
	 * @var array
	 */
	protected $form = null;

	/**
	 * Définir le message
	 * @var string
	 */
	protected $message = null;

	/**
	 * Définir le frontière entre les contenus.
	 *
	 * @var string
	 */
	protected $boundary;

    /**
     * @var string
     */
    protected $charset = "utf-8";

    /**
     * @var string
     */
    protected $type = "text/html";

	/**
	 * fromDefined
	 *
	 * @var boolean
	 */
	protected $fromDefined = false;

    /**
     *
     */
    protected function setDefaultHeader()
    {
        $this->headers[] = "Mime-Version: 1.0";
        $this->headers[] = "Date: " . date("r");
        $this->headers[] = "X-Mailer: Bow Framework";

        if ($this->form) {
            $this->headers[] = "From: " . $this->form;
        }

        if ($this->subject) {
            $this->headers[] = "Subject: " . $this->subject;
        }
    }

	/**
	 * formatHeader, formateur d'entête SMTP
	 *
	 * @return string
	 */
	private function formatHeader()
	{
        // Formatage de l'entête du mail
        $headers = implode(self::END, $this->headers). self::END;
        $headers .= "Content-Type: {$this->type}; charset=\"{$this->charset}\"". self::END;
        $headers .= "Content-Transfer-Encoding: 8bit" . self::END;
        $headers .= self::END . $this->message . self::END;

        return $headers;
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
     * @param $listDesc
     */
    public function toList(array $listDesc)
    {
        foreach($listDesc as $to) {
            $this->to[] = $this->formatEmail($to);
        }
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

	/**
	 * addFile, Permet d'ajout un fichier d'attachement
	 *
	 * @param string $file
	 * 
	 * @return self
     *
     * @throws MailException
	 */
	public function addFile($file)
	{
		if (!is_file($file)) {
            throw new MailException("Fichier introuvable.", E_USER_ERROR);
		}

        $this->attachement[] = $file;

		return $this;
	}

    /**
     * @return string
     */
    protected function makeSendData()
    {
        $this->headers[] = "Content-type: multipart/mixed; boundary=\"{$this->boundary}\"" . self::END;

        if (count($this->attachement) > 0) {
            foreach($this->attachement as $file) {
                $filename = basename($file);
                $this->headers[] = "--" . $this->boundary;
                $this->headers[] = "Content-Type: application/octet-stream; name=\"{$filename}\"";
                $this->headers[] = "Content-Transfer-Encoding: base64";
                $this->headers[] = "Content-Disposition: attachment" . self::END;
                $this->headers[] = chunk_split(base64_encode(file_get_contents($file)));
            }
            $this->headers[] = "--" . $this->boundary;
        }

        return $this->formatHeader();
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
	 * @param string $name
	 * 
	 * @return self
	 */
	public function from($from, $name = null)
	{
        if (!$this->fromDefined) {
            $this->form = ($name !== null) ? (ucwords($name) . " <{$from}>") : $from;
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
        $this->type($text, "text/plain");

		return $this;
	}

    /**
     * @param $data
     * @param $type
     * @return $this
     */
    private function type($data, $type)
    {
        if (!$this->message) {
            $this->type = $type;
            $this->message = $data;
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
		$this->headers[] = "Bcc: $mail";

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
     * send, envoie de mail.
     *
     * @param callable|null $cb
     * @return mixed
     */
    abstract public function send($cb = null);
}