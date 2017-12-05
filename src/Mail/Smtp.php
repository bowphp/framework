<?php
namespace Bow\Mail;

use ErrorException;
use Bow\Mail\Exception\SmtpException;
use Bow\Mail\Exception\SocketException;

/**
 * Class Smtp
 *
 * @author  Franck Dakia <dakiafranck@gmail.com>
 * @package Bow\Mail
 */
class Smtp implements Send
{

    /**
     * socket de connection
     *
     * @var null
     */
    private $sock;

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
     * @var int
     */
    private $timeout;

    /**
     * @var int
     */
    private $port = 25;

    /**
     * Constructor
     *
     * @param array $param
     */
    public function __construct(array $param)
    {
        if (!isset($param['secure'])) {
            $param['secure'] = false;

            if (!isset($param['tls'])) {
                $param['tls'] = false;
            }
        }

        $this->url = $param['hostname'];
        $this->username = $param['username'];
        $this->password = $param['password'];
        $this->secure = $param['ssl'];
        $this->tls = $param['tls'];
        $this->timeout = $param['timeout'];
        $this->port = $param['port'];
    }

    private function __clone()
    {
    }

    /**
     * Lance l'envoie de mail
     *
     * @param  Message $message
     * @return bool
     */
    public function send(Message $message)
    {
        $this->connection();
        $error = true;
        // SMTP command
        if ($this->username !== null) {
            $this->write('MAIL FROM: <' . $this->username . '>', 250);
        } else {
            if ($message->getFrom() !== null) {
                $this->write('MAIL FROM: <' . $message->getFrom() . '>', 250);
            }
        }

        foreach ($message->getTo() as $value) {
            if ($value[0] !== null) {
                $to = $value[0] . '<' . $value[1] . '>';
            } else {
                $to = '<' . $value[1] . '>';
            }

            $this->write('RCPT TO: ' . $to, 250);
        }

        $this->write('DATA', 354);
        $data = 'Subject: ' . $message->getSubject() . Message::END;
        $data .= $message->compileHeaders();
        $data .= 'Content-Type: ' . $message->getType() . '; charset=' . $message->getCharset() . Message::END;
        $data .= 'Content-Transfer-Encoding: 8bit' . Message::END;
        $data .= Message::END . $message->getMessage() . Message::END;
        $this->write($data);

        try {
            $this->write('.', 250);
        } catch (SmtpException $e) {
            echo $e->getMessage();
        }

        $status = $this->disconnect();

        if ($status == 221) {
            $error = false;
        }

        return (bool) $error;
    }


    /**
     * permet de se connecté a un serveur smpt
     *
     * @throws ErrorException
     * @throws SocketException | SmtpException
     */
    private function connection()
    {
        $url = $this->url;
        if ($this->secure === true) {
            $url = 'ssl://' . $this->url;
        }

        $this->sock = fsockopen($url, $this->port, $errno, $errstr, $this->timeout);

        if ($this->sock == null) {
            throw new SocketException('Impossible de se connected à ' . $this->url . ':' . $this->port, E_USER_ERROR);
        }

        stream_set_timeout($this->sock, $this->timeout, 0);
        $code = $this->read();

        $host = isset($_SERVER['HTTP_HOST']) && preg_match('/^[\w.-]+\z/', $_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

        if ($code == 220) {
            $code = $this->write('EHLO ' . $host, 250, 'HELO');
            if ($code != 250) {
                $this->write('EHLO ' . $host, 250, 'HELO');
            }
        }

        if ($this->tls === true) {
            $this->write('STARTTLS', 220);
            $secured = @stream_socket_enable_crypto($this->sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if (!$secured) {
                throw new ErrorException('Impossible de sécuriser votre connection avec tls', E_ERROR);
            }
        }

        if ($this->username !== null && $this->password !== null) {
            $this->write('AUTH LOGIN', 334);
            $this->write(base64_encode($this->username), 334, 'username');
            $this->write(base64_encode($this->password), 235, 'password');
        }
    }

    /**
     * déconnection
     */
    private function disconnect()
    {
        $r = $this->write('QUIT');
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

        for (; !feof($this->sock);) {
            if (($line = fgets($this->sock, 1e3)) != null) {
                $s = explode(' ', $line)[0];
                if (preg_match('#^[0-9]+$#', $s)) {
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
     * @param int    $code
     * @param null   $message
     *
     * @throws SmtpException
     * @return string
     */
    private function write($command, $code = null, $message = null)
    {
        if ($message == null) {
            $message = $command;
        }

        $command = $command . Message::END;
        fwrite($this->sock, $command, strlen($command));

        $response = null;


        if ($code !== null) {
            $response = $this->read();
            if (!in_array($response, (array) $code)) {
                throw new SmtpException('Serveur SMTP n\'a pas accepté ' . (isset($message) ? $message : '') . ' avec le code [' . $response . ']', E_ERROR);
            }
        }

        return $response;
    }
}
