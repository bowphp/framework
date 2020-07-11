<?php

namespace Bow\Mail;

use Bow\Mail\Exception\MailException;
use Bow\Support\Str;

class Message
{
    /**
     * The mail end of line
     *
     * @var string
     */
    const END = "\r\n";

    /**
     * List of headers
     *
     * @var array
     */
    private $headers = [];

    /**
     * Define the recipient
     *
     * @var array
     */
    private $to = [];

    /**
     * Define the recipient
     *
     * @var string
     */
    private $subject = null;

    /**
     * The mail attachment list
     *
     * @var array
     */
    private $attachment = [];

    /**
     * Define the mail sender
     *
     * @var string
     */
    private $from = null;

    /**
     * The mail message
     *
     * @var string
     */
    private $message = null;

    /**
     * Define the boundary between the contents.
     *
     * @var string
     */
    private $boundary;

    /**
     * The mail charset
     *
     * @var string
     */
    private $charset = "utf-8";

    /**
     * The mail message content-type
     *
     * @var string
     */
    private $type = "text/html";

    /**
     * The flag allows to enable sender
     *
     * @var boolean
     */
    private $fromDefined = false;

    /**
     * Message Constructor.
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
     * Set the default header
     *
     * @return void
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
     * Add personal headers
     *
     * @param string $key
     * @param string $value
     */
    public function addHeader($key, $value)
    {
        $this->headers[] = "$key: $value";
    }

    /**
     * Define the receiver
     *
     * @param string $to
     * @param null $name
     *
     * @return Message
     */
    public function to($to, $name = null)
    {
        $this->to[] = $this->formatEmail($to, $name);

        return $this;
    }

    /**
     * Define the receiver in list
     *
     * @param array $sendTo
     *
     * @return $this
     */
    public function toList(array $sendTo)
    {
        foreach ($sendTo as $name => $to) {
            $this->to[] = $this->formatEmail($to, !is_int($name) ? $name : null);
        }

        return $this;
    }

    /**
     * Format the email receiver
     *
     * @param string $email
     * @param null $name
     *
     * @return array
     */
    private function formatEmail($email, $name = null)
    {
        /**
         * Organization of the list of senders
         */
        if (!is_string($name) && preg_match('/^(.+)\s+<(.*)>\z$/', $email, $matches)) {
            array_shift($matches);
            $name = $matches[0];
            $email = $matches[1];
        }

        if (!Str::isMail($email)) {
            throw new \InvalidArgumentException("$email is not valid email.", E_USER_ERROR);
        }

        return [$name, $email];
    }

    /**
     * Add an attachment file
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
            throw new MailException("File not found.", E_USER_ERROR);
        }

        $this->attachment[] = $file;

        return $this;
    }

    /**
     * Compile the mail header
     *
     * @return string
     */
    public function compileHeaders()
    {
        if (count($this->attachment) > 0) {
            $this->headers[] = "Content-type: multipart/mixed; boundary=\"{$this->boundary}\"" . self::END;

            foreach ($this->attachment as $file) {
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
     * Define the subject of the mail
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
     * Define the sender of the mail
     *
     * @param string $from
     * @param null $name
     *
     * @return Message
     */
    public function from($from, $name = null)
    {
        $this->from = ($name !== null) ? (ucwords($name) . " <{$from}>") : $from;

        return $this;
    }

    /**
     * Define the type of content in text/html
     *
     * @param  string $html=null
     * @return Message
     */
    public function html($html)
    {
        return $this->type($html, "text/html");
    }

    /**
     * Add message body
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
     * Add message body and set message type
     *
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
     * @param null $name [optional]
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
     * Add carbon copy
     *
     * @param string $mail
     * @param null $name [optional]
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
     * Add Reply-To
     *
     * @param string $mail
     * @param null $name
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
     * Change the value of the boundary
     *
     * @param $boundary
     */
    protected function setBoundary($boundary)
    {
        $this->boundary = $boundary;
    }

    /**
     * Add Return-Path
     *
     * @param string $mail
     * @param null $name = null
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
     * Set email priority.
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
     * Edit the mail message
     *
     * @param $message
     * @param string $type
     */
    public function setMessage($message, $type = 'text/html')
    {
        $this->type = $type;

        $this->message = $message;
    }

    /**
     * Get the headers
     *
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Get the list of receivers
     *
     * @return array
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * Get the subject of the email
     *
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * Get the sender
     *
     * @return string
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * Get the email message
     *
     * @return string
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * Get the email encoding
     *
     * @return string
     */
    public function getCharset()
    {
        return $this->charset;
    }

    /**
     * Get Content-Type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Get the value of a variable that verifies that a sender is registered
     *
     * @return boolean
     */
    public function fromIsDefined()
    {
        return $this->fromDefined;
    }
}
