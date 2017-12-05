<?php
namespace Bow\Mail;

use Bow\Support\Str;
use Bow\Mail\Exception\MailException;

/**
 * Class Message
 *
 * @author  Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Mail
 */
class Message
{
    const END = "\r\n";

    /**
     * Liste des entêtes
     *
     * @var array
     */
    private $headers = [];

    /**
     * définir le destinataire
     *
     * @var array
     */
    private $to = [];

    /**
     * définir l'object du mail
     *
     * @var string
     */
    private $subject = null;

    /**
     * @var array
     */
    private $attachement = [];

    /**
     * @var string
     */
    private $from = null;

    /**
     * Définir le message
     *
     * @var string
     */
    private $message = null;

    /**
     * Définir le frontière entre les contenus.
     *
     * @var string
     */
    private $boundary;

    /**
     * @var string
     */
    private $charset = "utf-8";

    /**
     * @var string
     */
    private $type = "text/html";

    /**
     * fromDefined
     *
     * @var boolean
     */
    private $fromDefined = false;

    /**
     * Construction d'une instance de SimpleMail
     *
     * @param bool $boundary
     */
    public function __construct($boundary = true)
    {
        $this->setDefaultHeader();
        if ($boundary) {
            $this->setBoundary("__Bow-Framework-" . md5(date("r")));
        }
    }

    /**
     * Définir les entête par défaut
     */
    public function setDefaultHeader()
    {
        $this->headers[] = "Mime-Version: 1.0";
        $this->headers[] = "Date: " . date("r");
        $this->headers[] = "X-Mailer: PHP/".phpversion();

        if ($this->subject) {
            $this->headers[] = "Subject: " . $this->subject;
        }
    }

    /**
     * Ajout des entêtes personnel
     *
     * @param string $key
     * @param string $value
     */
    public function addHeader($key, $value)
    {
        $this->headers[] = "$key: $value";
    }

    /**
     * to, définir le récépteur
     *
     * @param string $to
     * @param string $name
     *
     * @return Message
     */
    public function to($to, $name = null)
    {
        $this->to[] = $this->formatEmail($to, $name);

        return $this;
    }

    /**
     * @param array $list_desc
     * @return $this
     */
    public function toList(array $list_desc)
    {
        foreach ($list_desc as $name => $to) {
            $this->to[] = $this->formatEmail($to, !is_int($name) ? $name : null);
        }

        return $this;
    }

    /**
     * Formaté l'email récu.
     *
     * @param string $email
     * @param string $name
     *
     * @return array
     */
    private function formatEmail($email, $name = null)
    {
        /**
         * Organisation de la liste des senders
         */
        if (!is_string($name) && preg_match('/^(.+)\s+<(.*)>\z$/', $email, $matches)) {
            array_shift($matches);
            $name = $matches[0];
            $email = $matches[1];
        }

        if (!Str::isMail($email)) {
            throw new \InvalidArgumentException("$email n'est pas email valide.", E_USER_ERROR);
        }

        return [$name, $email];
    }

    /**
     * addFile, Permet d'ajout un fichier d'attachement
     *
     * @param string $file
     *
     * @return Message
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
    public function compileHeaders()
    {
        if (count($this->attachement) > 0) {
            $this->headers[] = "Content-type: multipart/mixed; boundary=\"{$this->boundary}\"" . self::END;
            foreach ($this->attachement as $file) {
                $filename = basename($file);
                $this->headers[] = "--" . $this->boundary;
                $this->headers[] = "Content-Type: application/octet-stream; name=\"{$filename}\"";
                $this->headers[] = "Content-Transfer-Encoding: base64";
                $this->headers[] = "Content-Disposition: attachment" . self::END;
                $this->headers[] = chunk_split(base64_encode(file_get_contents($file)));
            }
            $this->headers[] = "--" . $this->boundary;
        }

        return implode(self::END, $this->headers).self::END;
    }

    /**
     * subject, Définit le suject du mail
     *
     * @param string $subject
     *
     * @return Message
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
     * @return Message
     */
    public function from($from, $name = null)
    {
        $this->from = ($name !== null) ? (ucwords($name) . " <{$from}>") : $from;

        return $this;
    }

    /**
     * toHtml, définir le type de contenu en text/html
     *
     * @param  string $html=null
     * @return Message
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
     * @return Message
     */
    public function text($text)
    {
        $this->type($text, "text/plain");

        return $this;
    }

    /**
     * @param string $message
     * @param string $type
     * @return Message
     */
    private function type($message, $type)
    {
        $this->type = $type;
        $this->message = $message;

        return $this;
    }

    /**
     * Adds blind carbon copy
     *
     * @param string $mail
     * @param string $name [optional]
     *
     * @return Message
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
     * @param string $name [optional]
     *
     * @return Message
     */
    public function addCc($mail, $name = null)
    {
        $mail = ($name !== null) ? (ucwords($name) . " <{$mail}>") : $mail;
        $this->headers[] = "Cc: $mail";

        return $this;
    }

    /**
     * Adds Reply-To
     *
     * @param string $mail
     * @param string $name=null
     *
     * @return Message
     */
    public function addReplyTo($mail, $name = null)
    {
        $mail = ($name !== null) ? (ucwords($name) . " <{$mail}>") : $mail;
        $this->headers[] = "Replay-To: $mail";

        return $this;
    }

    /**
     * Modifie la valeur de la frontière
     *
     * @param $boundary
     */
    protected function setBoundary($boundary)
    {
        $this->boundary = $boundary;
    }

    /**
     * Adds Return-Path
     *
     * @param string $mail
     * @param string $name=null
     *
     * @return Message
     */
    public function addReturnPath($mail, $name = null)
    {
        $mail = ($name !== null) ? (ucwords($name) . " <{$mail}>") : $mail;
        $this->headers[] = "Return-Path: $mail";

        return $this;
    }

    /**
     * Sets email priority.
     *
     * @param int $priority
     *
     * @return Message
     */
    public function addPriority($priority)
    {
        $this->headers[] = "X-Priority: " . (int) $priority;

        return $this;
    }

    /**
     * Modifir le message du mail
     *
     * @param $message
     */
    public function setMessage($message, $type = 'text/html')
    {
        $this->type = $type;
        $this->message = $message;
    }

    /**
     * Récupère les entêtes
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Récupère la liste des récepteurs
     *
     * @return array
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * Récupère l'objet du mail
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Récupère l'expéditeur
     *
     * @return string
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Récupère le message du mail
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Récupère l'encodage du mail
     *
     * @return string
     */
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * Récupère le type de contenu
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Récupère la valeur d'une variable qui permet de vérifier qu'un expéditeur est enrégistré
     *
     * @return boolean
     */
    public function fromIsDefined()
    {
        return $this->fromDefined;
    }
}
