<?php

declare(strict_types=1);

namespace Bow\Mail\Adapters;

use Bow\Mail\Contracts\MailAdapterInterface;
use Bow\Mail\Envelop;
use Bow\Mail\Exception\MailException;
use Bow\Mail\Exception\SmtpException;
use Bow\Mail\Exception\SocketException;
use Bow\Mail\Security\DkimSigner;
use Bow\Mail\Security\SpfChecker;
use ErrorException;

class SmtpAdapter implements MailAdapterInterface
{
    /**
     * Socket connection
     *
     * @var resource
     */
    private $sock;

    /**
     * The username
     *
     * @var ?string
     */
    private ?string $username;

    /**
     * The password
     *
     * @var ?string
     */
    private ?string $password;

    /**
     * The SMTP server
     *
     * @var ?string
     */
    private ?string $url;

    /**
     * Define the security
     *
     * @var bool
     */
    private ?bool $secure;

    /**
     * Enable TLS
     *
     * @var bool
     */
    private bool $tls = false;

    /**
     * Connexion time out
     *
     * @var int
     */
    private int $timeout;

    /**
     * The SMTP server
     *
     * @var int
     */
    private int $port = 25;

    /**
     * The DKIM signer
     *
     * @var ?DkimSigner
     */
    private ?DkimSigner $dkimSigner = null;

    /**
     * The SPF checker
     *
     * @var ?SpfChecker
     */
    private ?SpfChecker $spfChecker = null;

    /**
     * Smtp Constructor
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        if (!isset($config['secure']) || is_null($config['secure'])) {
            $config['secure'] = false;
        }

        if (!isset($config['tls']) || is_null($config['tls'])) {
            $config['tls'] = false;
        }

        $this->url = $config['hostname'];
        $this->username = $config['username'];
        $this->password = $config['password'];
        $this->secure = (bool)$config['ssl'];
        $this->tls = (bool)$config['tls'];
        $this->timeout = (int)$config['timeout'];
        $this->port = (int)$config['port'];

        if (isset($config['dkim']) && $config['dkim']['enabled']) {
            $this->dkimSigner = new DkimSigner($config['dkim']);
        }

        if (isset($config['spf']) && $config['spf']['enabled']) {
            $this->spfChecker = new SpfChecker($config['spf']);
        }
    }

    /**
     * Start sending mail
     *
     * @param Envelop $envelop
     * @return bool
     * @throws SocketException
     * @throws ErrorException
     */
    public function send(Envelop $envelop): bool
    {
        // Validate SPF if enabled
        if ($this->spfChecker !== null) {
            $senderIp = $_SERVER['REMOTE_ADDR'] ?? '';
            $senderEmail = $envelop->getFrom();
            $senderHelo = gethostname();

            $spfResult = $this->spfChecker->verify($senderIp, $senderEmail, $senderHelo);
            if ($spfResult === 'fail') {
                throw new MailException('SPF verification failed');
            }
        }

        // Add DKIM signature if enabled
        if ($this->dkimSigner !== null) {
            $dkimHeader = $this->dkimSigner->sign($envelop);
            $envelop->addHeader('DKIM-Signature', $dkimHeader);
        }

        $this->connection();

        $error = true;

        // SMTP command
        if ($envelop->getFrom() !== null) {
            $this->write('MAIL FROM: <' . $envelop->getFrom() . '>', 250);
        } elseif ($this->username !== null) {
            $this->write('MAIL FROM: <' . $this->username . '>', 250);
        }

        foreach ($envelop->getTo() as $value) {
            if ($value[0] !== null) {
                $to = $value[0] . ' <' . $value[1] . '>';
            } else {
                $to = '<' . $value[1] . '>';
            }

            $this->write('RCPT TO: ' . $to, 250);
        }

        $envelop->setDefaultHeader();

        $this->write('DATA', 354);

        $data = 'Subject: ' . $envelop->getSubject() . Envelop::END;
        $data .= $envelop->compileHeaders();
        $data .= 'Content-Type: ' . $envelop->getType() . '; charset=' . $envelop->getCharset() . Envelop::END;
        $data .= 'Content-Transfer-Encoding: 8bit' . Envelop::END;
        $data .= Envelop::END . $envelop->getMessage() . Envelop::END;

        $this->write($data);

        try {
            $this->write('.', 250);
        } catch (SmtpException $e) {
            app("logger")->error($e->getMessage(), $e->getTraceAsString());
            error_log($e->getMessage());
        }

        $status = $this->disconnect();

        if ($status == 221) {
            $error = false;
        }

        return (bool)$error;
    }


    /**
     * Connect to an SMTP server
     *
     * @throws ErrorException
     * @throws SocketException | SmtpException
     */
    private function connection(): void
    {
        $url = $this->url;

        if ($this->secure === true) {
            $url = 'ssl://' . $this->url;
        }

        $sock = fsockopen($url, $this->port, $errno, $errstr, $this->timeout);

        if ($sock == null) {
            throw new SocketException(
                'Impossible to get connected to ' . $this->url . ':' . $this->port,
                E_USER_ERROR
            );
        }

        $this->sock = $sock;
        stream_set_timeout($this->sock, $this->timeout);
        $code = $this->read();

        // The client sends this command to the SMTP server to identify
        // itself and initiate the SMTP conversation.
        // The domain name or IP address of the SMTP client is usually sent as an argument
        // together with the command (e.g. "EHLO client.example.com").
        $client_host = isset($_SERVER['HTTP_HOST'])
        && preg_match('/^[\w.-]+\z/', $_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';

        if ($code == 220) {
            $code = $this->write('EHLO ' . $client_host, 250, 'HELO');
            if ($code != 250) {
                $this->write('EHLO ' . $client_host, 250, 'HELO');
            }
        }

        if ($this->tls === true) {
            $this->write('STARTTLS', 220);

            $secured = @stream_socket_enable_crypto($this->sock, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);

            if (!$secured) {
                throw new ErrorException(
                    'Can not secure your connection with tls',
                    E_ERROR
                );
            }
        }

        if ($this->username !== null && $this->password !== null) {
            $this->write('AUTH LOGIN', 334);
            $this->write(base64_encode($this->username), 334, 'username');
            $this->write(base64_encode($this->password), 235, 'password');
        }
    }

    /**
     * Read the current connection stream.
     *
     * @return int
     */
    private function read(): int
    {
        $s = null;

        for (; !feof($this->sock);) {
            if (($line = fgets($this->sock, 1000)) == null) {
                continue;
            }

            $s = explode(' ', $line)[0];

            if (preg_match('#^[0-9]+$#', $s)) {
                break;
            }
        }

        return (int)$s;
    }

    /**
     * Start an SMTP command
     *
     * @param string $command
     * @param ?int $code
     * @param ?string $envelop
     * @return int|null
     * @throws SmtpException
     */
    private function write(string $command, ?int $code = null, ?string $envelop = null): ?int
    {
        if ($envelop == null) {
            $envelop = $command;
        }

        $command = $command . Envelop::END;

        fwrite($this->sock, $command, strlen($command));

        $response = null;

        if ($code === null) {
            return null;
        }

        $response = $this->read();

        if (!in_array($response, (array)$code)) {
            throw new SmtpException(
                sprintf('SMTP server did not accept %s with code [%s]', $envelop, $response),
                E_ERROR
            );
        }

        return $response;
    }

    /**
     * Disconnection
     *
     * @return int|string|null
     * @throws ErrorException
     */
    private function disconnect(): int|string|null
    {
        $r = $this->write('QUIT');

        fclose($this->sock);

        $this->sock = null;

        return $r;
    }
}
