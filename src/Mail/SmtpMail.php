<?php

namespace System\Mail;

use ErrorException;
use System\Exception\SmtpException;
use System\Exception\SocketException;

class SmtpMail implements IHeader
{

    /**
     * socket de connection
     *
     * @var null
     */
    private $sock = null;

    /**
     *
     * @var null|SmtpMail
     */
    private static $inst = null;

    /**
     *
     * @var null
     */
    private $connection = null;

    /**
     *
     * @var string
     */
    private $sep;

    /**
     * @return self
     */
    private function __construct()
    {
        if (defined('PHP_EOL')) {
            $this->sep = PHP_EOL;
        } else {
            $this->sep = (strpos(PHP_OS, 'WIN') === false) ? "\n" : "\r\n";
        }
    }

    private function __clone(){}

    /**
     * factory charge un instance de la classe.
     *
     * @return SmtpMail
     */
    public static function load()
    {
        if (self::$inst !== null) {
            self::$inst = new self;
        }
        return self::$inst;
    }

    /**
     * @param string $mail
     * @param string $name
     * @return self
     */
    public function addCc($mail, $name = null) {
        return $this;
    }

    /**
     * @param string $mail
     * @param string $name
     * @return self
     */
    public function addBcc($mail, $name = null) {
        return $this;
    }

    /**
     * @param string $mail
     * @param string $name
     * @return self
     */
    public function addReplayTo($mail, $name = null) {
        return $this;
    }

    /**
     * @param string $mail
     * @param string $name
     * @return self
     */
    public function addReturnPath($mail, $name = null) {
        return $this;
    }

    /**
     * @param string $mail
     * @param $name
     * @param bool $smtp
     * @return self
     */
    public function to($mail, $name = null, $smtp = false) {
        return $this;
    }

    /**
     * @param string $mail
     * @param string $name
     * @return self
     */
    public function from($mail, $name) {
        return $this;
    }

    /**
     * Lance l'envoie de mail
     *
     * @param callable $cb=null
     * @return self
     */
    public function send($cb = null) {
        $this->disconnect();
        return $this;
    }


    /**
     * permet de se connecté a un serveur smpt
     *
     * @param $url
     * @param null $username
     * @param null $password
     * @param bool|false $secure
     * @param bool|false $tls
     * @throws ErrorException
     * @throws SocketException
     * @throws SmtpException
     */
    public function connection($url, $username = null, $password = null, $secure = false, $tls = false)
    {
        @list($url, $port) = explode(":", $url, 2);

        if (!isset($port)) {
            $port = 25;
        } else {
            $port = (int) $port;
        }

        if ($secure === true) {
            $url = "ssl://{$url}";
            $port = 586;
        }

        $this->sock = fsockopen($url, $port, $errno, $errstr, 50);

        if ($this->sock === null) {
            throw new SocketException(__METHOD__."(): can not connect to {$url}:{$port}", E_USER_ERROR);
        }

        stream_set_timeout($this->sock, 20, 0);
        $this->read();
        $host = isset($_SERVER['HTTP_HOST']) && preg_match('#^[\w.-]+\z#', $_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
        $this->write("EHLO $host", 250);

        if ((int) $this->read() != 250) {
            $this->write("EHLO $host", 250);
        }

        if ($tls === true) {
            $this->write("STARTTLS", 220);
            $secured = stream_socket_enable_crypto($this->connection, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if (!$secured) {
                throw new ErrorException(__METHOD__."(): Can not secure you connection with tls.", 1);
            }
            $this->write("EHLO $host", $code=250);
        }

        if ($username !== null && $password !== null) {
            $this->write("AUTH LOGIN", 334);
            $this->write(base64_encode($username), 334, "username");
            $this->write(base64_encode($password), 235, "password");
        }

    }

    /**
     * déconnection
     */
    private function disconnect()
    {
        fclose($this->connection);
        $this->connection = null;
    }

    /**
     * Lire le flux de connection courrant.
     *
     * @return string
     */
    private function read()
    {
        $s = "";
        while (!feof($this->connection)) {
            if (($line = fgets($this->connection, 1e3) != null)) {
                $s .= $line;
                if (substr($line, 3, 1) == " ") {
                    break;
                }
            }
        }
        return $s;
    }

    /**
     * Lance une commande SMPT
     *
     * @param string $command
     * @param int $code
     * @param null $message
     * @throws SmtpException
     */
    private function write($command, $code, $message = null)
    {
        fwrite($this->connection, $command . $this->sep);
        if ($code) {
            $response = $this->read();
            if (!in_array((int) $response, (array) $code, true)) {
                throw new SmtpException("Serveur SMTP did not accepted " . (isset($message) ? $message : '') . ". Avec l'error: $response", 1);
            }
        }
    }

}