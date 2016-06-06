<?php
namespace Bow\Mail;

use ErrorException;
use Bow\Support\Str;
use Bow\Support\Util;
use Bow\Exception\SmtpException;
use Bow\Exception\SocketException;

/**
 * Class Smtp
 *
 * @author Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Mail
 */
 class Smtp extends Message implements Send
{

    /**
     * socket de connection
     *
     * @var null
     */
    private $sock = null;

    /**
     * @var string
     */
    private $username;

    /**
     * @var string
     */
    private $password;

    /**
     * @var string
     */
    private $url;

    /**
     * @var bool
     */
    private $secure;

    /**
     * @var bool
     */
    private $tls = false;

    /**
     * Constructor
     * 
     * @param array $param
     * 
     * @return self
     */
    public function __construct(array $param)
    {
        $this->setBoundary("Bow-Framework-" . md5(date("r")));

        if (!isset($param["secure"])) {
            $param["secure"] = false;

            if (!isset($param["tls"])) {
                $param["tls"] = false;
            }
        }

        $this->url = $param["hostname"];
        $this->username = $param["username"];
        $this->password = $param["password"];
        $this->secure = $param["secure"];
        $this->tls = $param["tls"];
    }

    private function __clone() {}

    /**
     * Lance l'envoie de mail
     *
     * @param callable $cb=null
     * @return self
     */
    public function send($cb = null)
    {
        $this->connection();
        $error = true;
        $this->setDefaultHeader();
        // SMTP command
        if ($this->username === null && $this->password === null) {
            $this->write("MAIL FROM: <test@0.0.0.0>", 250);
        } else {
            $this->write("MAIL FROM: <" . $this->username . ">", 250);
        }


        foreach($this->getTo() as $value) {
            $to = "";
            if ($value[0] !== null) {
                $to .= "{$value[0]} <{$value[1]}>";
            } else {
                $to .= "<{$value[1]}>";
            }
            $this->write("RCPT TO: " . $to, 250);
        }

        $this->write("DATA", 354);
        $data  = $this->compileHeaders();
        $data .= "Content-Type: {$this->getType()}; charset=\"{$this->getCharset()}\"". self::END;
        $data .= "Content-Transfer-Encoding: 8bit" . self::END;
        $data .= self::END . $this->getMessage() . self::END;
        $this->write($data);

        try {
            $this->write(".", 250);
        } catch(SmtpException $e) {
            echo $e->getMessage();
        }

        $status = $this->disconnect();

        if ($status == 221) {
            $error = null;
        }

        if (is_callable($cb)) {
            Util::launchCallback($cb, $error);
        }

        return $status;
    }


    /**
     * permet de se connecté a un serveur smpt
     *
     * @throws ErrorException
     * @throws SocketException | SmtpException
     */
    private function connection()
    {
        @list($url, $port) = explode(":", $this->url, 2);

        if (!isset($port)) {
            $port = 25;
        } else {
            $port = (int) $port;
        }

        if ($this->secure === true) {
            $url = "ssl://$url";
            $port = 465;
        }

        $this->sock = fsockopen($url, $port, $errno, $errstr, $timeout=50);

        if ($this->sock == null) {
            throw new SocketException("Can not connect to {$url}:{$port}", E_USER_ERROR);
        }

        stream_set_timeout($this->sock, 20, 0);
        $code = $this->read();

        $host = isset($_SERVER['HTTP_HOST']) && preg_match('/^[\w.-]+\z/', $_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

        if ($code == 220) {
            $code = $this->write("EHLO $host", 250);
            if ($code != 250) {
                $this->write("EHLO $host", 250);
            }
        }

        if ($this->tls === true) {
            $this->write("STARTTLS", 220);
            $secured = stream_socket_enable_crypto($this->sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if (!$secured) {
                throw new ErrorException("Can not secure you connection with tls.",E_ERROR);
            }
        }

        if ($this->username !== null && $this->password !== null) {
            $this->write("AUTH LOGIN", 334);
            $this->write(base64_encode($this->username), $code=334, "username");
            $this->write(base64_encode($this->password), $code=235, "password");
        }
    }

    /**
     * déconnection
     */
    private function disconnect()
    {
        $r = $this->write("QUIT" . Util::sep());
        fclose($this->sock);
        $this->sock = null;

        return $r;
    }

    /**
     * Lire le flux de connection courrant.
     *
     * @return string
     */
    private function read()
    {
        $s = null;

        for (; !feof($this->sock); ) {
            if (($line = fgets($this->sock, 1e3)) != null) {
                $s = explode(" ", $line)[0];
                if (preg_match("#^[0-9]+$#", $s)) {
                    break;
                }
            }
        }

        return (int) $s;
    }

    /**
     * Lance une commande SMPT
     *
     * @param string $command
     * @param int $code
     * @param null $message
     * 
     * @throws SmtpException
     * @return string
     */
    private function write($command, $code = null, $message = null)
    {
        $command = $command . self::END;
        echo $command;
        fwrite($this->sock, $command, strlen($command));
        
        $response = null;

        if ($code !== null) {
            $response = $this->read();
            if (!in_array($response, (array) $code)) {
                throw new SmtpException("Serveur SMTP did not accepted " . (isset($message) ? $message : '') . ". Avec l'error: $response", E_ERROR);
            }
        }

        return $response;
    }
}
