<?php

declare(strict_types=1);

namespace Bow\Mail;

use Bow\Mail\Exception\MailException;
use Bow\Support\Str;
use InvalidArgumentException;

class Message
{
    /**
     * The mail end of line
     *
     * @var string
     */
    public const END = "\r\n";

    /**
     * List of headers
     *
     * @var array
     */
    private array $headers = [];

    /**
     * Define the recipient
     *
     * @var array
     */
    private array $to = [];

    /**
     * Define the recipient
     *
     * @var ?string
     */
    private ?string $subject = null;

    /**
     * The mail attachment list
     *
     * @var array
     */
    private array $attachment = [];

    /**
     * Define the mail sender
     *
     * @var ?string
     */
    private ?string $from = null;

    /**
     * The mail message
     *
     * @var ?string
     */
    private ?string $message = null;

    /**
     * Define the boundary between the contents.
     *
     * @var ?string
     */
    private ?string $boundary;

    /**
     * The mail charset
     *
     * @var string
     */
    private string $charset = "utf-8";

    /**
     * The mail message content-type
     *
     * @var string
     */
    private string $type = "text/html";

    /**
     * The flag allows to enable sender
     *
     * @var bool
     */
    private bool $fromDefined = false;

    /**
     * Message Constructor.
     *
     * @param bool $boundary
     */
    public function __construct(bool $boundary = true)
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
    public function setDefaultHeader(): void
    {
        $this->headers[] = "Mime-Version: 1.0";
        $this->headers[] = "Date: " . date("r");
        $this->headers[] = "X-Mailer: PHP/" . phpversion();

        if ($this->subject) {
            $this->headers[] = "Subject: " . $this->subject;
        }
    }

    /**
     * Change the value of the boundary
     *
     * @param string $boundary
     */
    protected function setBoundary(string $boundary): void
    {
        $this->boundary = $boundary;
    }

    /**
     * Add personal headers
     *
     * @param string $key
     * @param string $value
     */
    public function addHeader(string $key, string $value): void
    {
        $this->headers[] = "$key: $value";
    }

    /**
     * Define the receiver
     *
     * @param string $to
     * @param ?string $name
     *
     * @return Message
     */
    public function to(string $to, ?string $name = null): Message
    {
        $this->to[] = $this->formatEmail($to, $name);

        return $this;
    }

    /**
     * Format the email receiver
     *
     * @param string $email
     * @param ?string $name
     * @return array
     */
    private function formatEmail(string $email, ?string $name = null): array
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
            throw new InvalidArgumentException("$email is not valid email.", E_USER_ERROR);
        }

        return [$name, $email];
    }

    /**
     * Define the receiver in list
     *
     * @param array $recipients
     * @return $this
     */
    public function toList(array $recipients): Message
    {
        foreach ($recipients as $name => $to) {
            $this->to[] = $this->formatEmail($to, !is_int($name) ? $name : null);
        }

        return $this;
    }

    /**
     * Add an attachment file
     *
     * @param string $file
     * @return Message
     * @throws MailException
     */
    public function addFile(string $file): Message
    {
        if (!is_file($file)) {
            throw new MailException("The $file file was not found.", E_USER_ERROR);
        }

        $this->attachment[] = $file;

        return $this;
    }

    /**
     * Compile the mail header
     *
     * @return string
     */
    public function compileHeaders(): string
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

        return implode(self::END, $this->headers) . self::END;
    }

    /**
     * Define the subject of the mail
     *
     * @param string $subject
     * @return Message
     */
    public function subject(string $subject): Message
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Define the sender of the mail
     *
     * @param string $from
     * @param ?string $name
     * @return Message
     */
    public function from(string $from, ?string $name = null): Message
    {
        $this->from = ($name !== null) ? (ucwords($name) . " <{$from}>") : $from;

        return $this;
    }

    /**
     * Define the type of content in text/html
     *
     * @param string $html
     * @return Message
     */
    public function html(string $html): Message
    {
        return $this->type($html, "text/html");
    }

    /**
     * Add message body and set message type
     *
     * @param string $message
     * @param string $type
     * @return Message
     */
    private function type(string $message, string $type): Message
    {
        $this->type = $type;

        $this->message = $message;

        return $this;
    }

    /**
     * Add message body
     *
     * @param string $text
     * @return Message
     */
    public function text(string $text): Message
    {
        $this->type($text, "text/plain");

        return $this;
    }

    /**
     * Adds blind carbon copy
     *
     * @param string $mail
     * @param ?string $name
     *
     * @return Message
     */
    public function addBcc(string $mail, ?string $name = null): Message
    {
        $mail = ($name !== null) ? (ucwords($name) . " <{$mail}>") : $mail;

        $this->headers[] = "Bcc: $mail";

        return $this;
    }

    /**
     * Add carbon copy
     *
     * @param string $mail
     * @param ?string $name
     *
     * @return Message
     */
    public function addCc(string $mail, ?string $name = null): Message
    {
        $mail = ($name !== null) ? (ucwords($name) . " <{$mail}>") : $mail;

        $this->headers[] = "Cc: $mail";

        return $this;
    }

    /**
     * Add Reply-To
     *
     * @param string $mail
     * @param ?string $name
     * @return Message
     */
    public function addReplyTo(string $mail, ?string $name = null): Message
    {
        $mail = ($name !== null) ? (ucwords($name) . " <{$mail}>") : $mail;

        $this->headers[] = "Replay-To: $mail";

        return $this;
    }

    /**
     * Add Return-Path
     *
     * @param string $mail
     * @param ?string $name = null
     *
     * @return Message
     */
    public function addReturnPath(string $mail, ?string $name = null): Message
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
    public function addPriority(int $priority): Message
    {
        $this->headers[] = "X-Priority: " . (int)$priority;

        return $this;
    }

    /**
     * @param string $message
     * @param string $type
     * @see setMessage
     */
    public function message(string $message, string $type = 'text/html'): void
    {
        $this->setMessage($message, $type);
    }

    /**
     * Get the headers
     *
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get the list of receivers
     *
     * @return array
     */
    public function getTo(): array
    {
        return $this->to;
    }

    /**
     * Get the subject of the email
     *
     * @return ?string
     */
    public function getSubject(): ?string
    {
        return $this->subject;
    }

    /**
     * Get the sender
     *
     * @return ?string
     */
    public function getFrom(): ?string
    {
        return $this->from;
    }

    /**
     * Get the email message
     *
     * @return ?string
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * Edit the mail message
     *
     * @param string $message
     * @param string $type
     */
    public function setMessage(string $message, string $type = 'text/html'): void
    {
        $this->type = $type;

        $this->message = $message;
    }

    /**
     * Get the email encoding
     *
     * @return ?string
     */
    public function getCharset(): ?string
    {
        return $this->charset;
    }

    /**
     * Get Content-Type
     *
     * @return ?string
     */
    public function getType(): ?string
    {
        return $this->type ?? 'text/html';
    }

    /**
     * Get the value of a variable that verifies that a sender is registered
     *
     * @return bool
     */
    public function fromIsDefined(): bool
    {
        return $this->fromDefined;
    }
}
