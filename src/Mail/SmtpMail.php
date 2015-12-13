<?php


namespace System;

    /**
     * Class SmtpMail
     * @package System
     */
/**
 * Class SmtpMail
 * @package System
 */
class SmtpMail implements IHeader
{

    private static $inst = null;
    private $connection = null;
    private $sep;
    /**
     * @return self
     */
    private function __construct()
    {
        if (self::$inst !== null) {
            if (defined('PHP_EOL')) {
                $this->sep = PHP_EOL;
            } else {
                $this->sep = (strpos(PHP_OS, 'WIN') === false) ? "\n" : "\r\n";
            }
            self::$inst = new self;
        }

        return self::$inst;
    }

    private function __clone(){}

    /**
     * @return SmtpMail
     */
    public static function load()
    {
        return self::__construct();
    }

    /**
     * @var null
     */
    private $sock = null;

    /**
     * @param string $mail
     * @return self
     */
    public function addCc($mail, $name = null) {
        return $this;
    }

    /**
     * @param string $mail
     * @return self
     */
    public function addBcc($mail, $name = null) {
        return $this;
    }

    /**
     * @param string $mail
     * @return self
     */
    public function addReplayTo($mail, $name = null) {
        return $this;
    }

    /**
     * @param string $mail
     * @return self
     */
    public function addReturnPath($mail, $name = null) {
        return $this;
    }

    /**
     * @param string $mail
     * @return self
     */
    public function to($mail, $name = null, $smtp = false) {
        return $this;
    }

    /**
     * @param string $mail
     * @return self
     */
    public function from($mail, $name) {
        return $this;
    }

    /**
     * @param callable $cb=null
     * @return self
     */
    public function send($cb = null) {
        $this->disconnect();
        return $this;
    }

    /**
     * @param string $url
     * @param string|null $username
     * @param string|null $password
     * @param bool|false $secure
     * @param bool|false $tls
     * @throws \ErrorException
     * @throws Exception\SOCKException
     * @throws Exception\SnoopSmptException
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
            throw new Exception\SOCKException(__METHOD__."(): can not connect to {$url}:{$port}", E_USER_ERROR);
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
                throw new \ErrorException(__METHOD__."(): Can not secure you connection with tls.", 1);
            }
            $this->write("EHLO $host", $code=250);
        }

        if ($username !== null && $password !== null) {
            $this->write("AUTH LOGIN", 334);
            $this->write(base64_encode($username), 334, "username");
            $this->write(base64_encode($password), 235, "password");
        }

    }

    private function disconnect()
    {
        fclose($this->connection);
        $this->connection = null;
    }

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
     * @param $command
     * @param $code
     * @param null $message
     * @throws Exception\SnoopSmptException
     */
    private function write($command, $code, $message = null)
    {
        fwrite($this->connection, $command . $this->sep);
        if ($code) {
            $response = $this->read();
            if (!in_array((int) $response, (array) $code, true)) {
                throw new Exception\SnoopSmptException("Serveur SMTP did not accepted " . (isset($message) ? $message : '') . ". Avec l'error: $response", 1);
            }
        }
    }

}