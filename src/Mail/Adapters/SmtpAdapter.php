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
     * SMTP response codes
     */
    private const SMTP_READY = 220;
    private const SMTP_OK = 250;
    private const SMTP_AUTH_CONTINUE = 334;
    private const SMTP_AUTH_SUCCESS = 235;
    private const SMTP_DATA_START = 354;
    private const SMTP_QUIT = 221;

    /**
     * Socket connection resource
     *
     * @var resource|null
     */
    private $socket = null;

    /**
     * SMTP server hostname
     *
     * @var string
     */
    private string $hostname;

    /**
     * SMTP authentication username
     *
     * @var string|null
     */
    private ?string $username;

    /**
     * SMTP authentication password
     *
     * @var string|null
     */
    private ?string $password;

    /**
     * Enable SSL/TLS encryption
     *
     * @var bool
     */
    private bool $secure;

    /**
     * Enable STARTTLS command
     *
     * @var bool
     */
    private bool $tls;

    /**
     * Connection timeout in seconds
     *
     * @var int
     */
    private int $timeout;

    /**
     * SMTP server port
     *
     * @var int
     */
    private int $port;

    /**
     * DKIM email signature handler
     *
     * @var DkimSigner|null
     */
    private ?DkimSigner $dkimSigner = null;

    /**
     * SPF email verification handler
     *
     * @var SpfChecker|null
     */
    private ?SpfChecker $spfChecker = null;

    /**
     * Indicates if currently connected to SMTP server
     *
     * @var bool
     */
    private bool $connected = false;

    /**
     * SmtpAdapter Constructor
     *
     * @param array $config SMTP configuration array
     * @throws MailException If required configuration is missing
     */
    public function __construct(array $config)
    {
        $this->validateConfiguration($config);
        $this->initializeConfiguration($config);
        $this->initializeSecurityFeatures($config);
    }

    /**
     * Validate required configuration parameters
     *
     * @param array $config
     * @throws MailException
     */
    private function validateConfiguration(array $config): void
    {
        $required = ['hostname', 'port', 'timeout'];

        foreach ($required as $key) {
            if (!isset($config[$key])) {
                throw new MailException("Missing required SMTP configuration: {$key}");
            }
        }
    }

    /**
     * Initialize SMTP configuration from array
     *
     * @param array $config
     */
    private function initializeConfiguration(array $config): void
    {
        $this->hostname = $config['hostname'];
        $this->username = $config['username'] ?? null;
        $this->password = $config['password'] ?? null;
        $this->secure = (bool)($config['ssl'] ?? false);
        $this->tls = (bool)($config['tls'] ?? false);
        $this->timeout = (int)$config['timeout'];
        $this->port = (int)$config['port'];
    }

    /**
     * Initialize security features (DKIM and SPF)
     *
     * @param array $config
     */
    private function initializeSecurityFeatures(array $config): void
    {
        if (!empty($config['dkim']['enabled'])) {
            $this->dkimSigner = new DkimSigner($config['dkim']);
        }

        if (!empty($config['spf']['enabled'])) {
            $this->spfChecker = new SpfChecker($config['spf']);
        }
    }


    /**
     * Send email via SMTP
     *
     * @param Envelop $envelop Email envelope containing message data
     * @return bool True on successful send, false otherwise
     * @throws SocketException If connection fails
     * @throws SmtpException If SMTP command fails
     * @throws MailException If SPF verification fails
     * @throws ErrorException If TLS negotiation fails
     */
    public function send(Envelop $envelop): bool
    {
        try {
            $this->validateEnvelop($envelop);
            $this->performSecurityChecks($envelop);
            $this->connect();
            $this->sendMailTransaction($envelop);

            return true;
        } catch (SmtpException | SocketException $e) {
            $this->logError($e);
            return false;
        } finally {
            $this->disconnect();
        }
    }

    /**
     * Validate email envelope has required data
     *
     * @param Envelop $envelop
     * @throws MailException
     */
    private function validateEnvelop(Envelop $envelop): void
    {
        if (empty($envelop->getTo())) {
            throw new MailException('No recipients specified');
        }

        if ($envelop->getMessage() === null || $envelop->getMessage() === '') {
            throw new MailException('No message content specified');
        }
    }

    /**
     * Perform SPF and DKIM security checks
     *
     * @param Envelop $envelop
     * @throws MailException If SPF verification fails
     */
    private function performSecurityChecks(Envelop $envelop): void
    {
        // Validate SPF if enabled
        if ($this->spfChecker !== null) {
            $senderIp = $_SERVER['REMOTE_ADDR'] ?? '';
            $senderEmail = $envelop->getFrom();
            $senderHelo = gethostname() ?: 'localhost';

            $spfResult = $this->spfChecker->verify($senderIp, $senderEmail, $senderHelo);

            if ($spfResult === 'fail') {
                throw new MailException('SPF verification failed for sender: ' . $senderEmail);
            }
        }

        // Add DKIM signature if enabled
        if ($this->dkimSigner !== null) {
            $dkimHeader = $this->dkimSigner->sign($envelop);
            $envelop->withHeader('DKIM-Signature', $dkimHeader);
        }
    }

    /**
     * Execute complete SMTP mail transaction
     *
     * @param Envelop $envelop
     * @throws SmtpException
     */
    private function sendMailTransaction(Envelop $envelop): void
    {
        $this->sendMailFrom($envelop);
        $this->sendRecipients($envelop);
        $this->sendData($envelop);
    }

    /**
     * Send MAIL FROM command
     *
     * @param Envelop $envelop
     * @throws SmtpException
     */
    private function sendMailFrom(Envelop $envelop): void
    {
        $from = $envelop->getFrom();

        if ($from !== null) {
            // Extract email address from "Name <email>" format if present
            $email = $this->extractEmailAddress($from);
            $this->executeCommand('MAIL FROM: <' . $email . '>', self::SMTP_OK);
        } elseif ($this->username !== null) {
            $this->executeCommand('MAIL FROM: <' . $this->username . '>', self::SMTP_OK);
        } else {
            throw new SmtpException('No sender email address specified');
        }
    }

    /**
     * Send RCPT TO commands for all recipients
     *
     * @param Envelop $envelop
     * @throws SmtpException
     */
    private function sendRecipients(Envelop $envelop): void
    {
        foreach ($envelop->getTo() as $recipient) {
            $to = $this->formatRecipient($recipient);
            $this->executeCommand('RCPT TO: ' . $to, self::SMTP_OK);
        }
    }

    /**
     * Format recipient for SMTP RCPT TO command
     * SMTP RCPT TO requires only the email address in angle brackets
     *
     * @param array $recipient [name, email]
     * @return string Formatted recipient (email only)
     */
    private function formatRecipient(array $recipient): string
    {
        [, $email] = $recipient;
        return '<' . $email . '>';
    }

    /**
     * Extract email address from a string that may contain "Name <email>" format
     *
     * @param string $address Email address possibly with display name
     * @return string Pure email address
     */
    private function extractEmailAddress(string $address): string
    {
        // If the address contains angle brackets, extract the email
        if (preg_match('/<(.+?)>/', $address, $matches)) {
            return $matches[1];
        }

        // Otherwise, return the address as-is (assuming it's already a pure email)
        return $address;
    }

    /**
     * Send email data (headers and body)
     *
     * @param Envelop $envelop
     * @throws SmtpException
     */
    private function sendData(Envelop $envelop): void
    {
        $envelop->setDefaultHeader();

        $this->executeCommand('DATA', self::SMTP_DATA_START);

        $data = $this->buildEmailData($envelop);
        $this->writeToSocket($data);

        $this->executeCommand('.', self::SMTP_OK);
    }

    /**
     * Build complete email data string
     *
     * @param Envelop $envelop
     * @return string Complete email data with headers and body
     */
    private function buildEmailData(Envelop $envelop): string
    {
        $data = 'Subject: ' . $envelop->getSubject() . Envelop::END;
        $data .= $envelop->compileHeaders();
        $data .= 'Content-Type: ' . $envelop->getType() . '; charset=' . $envelop->getCharset() . Envelop::END;
        $data .= 'Content-Transfer-Encoding: 8bit' . Envelop::END;
        $data .= Envelop::END . $envelop->getMessage() . Envelop::END;

        return $data;
    }

    /**
     * Log SMTP errors
     *
     * @param \Throwable $exception
     */
    private function logError(\Throwable $exception): void
    {
        $message = sprintf(
            'SMTP Error: %s [Code: %s]',
            $exception->getMessage(),
            $exception->getCode()
        );

        if (function_exists('app')) {
            try {
                $logger = app('logger');
                if ($logger) {
                    $logger->error($message, [
                        'exception' => $exception,
                        'trace' => $exception->getTraceAsString()
                    ]);
                }
            } catch (\Exception $e) {
                // Logger not available, fallback to error_log
            }
        }

        error_log($message);
    }

    /**
     * Establish connection to SMTP server
     *
     * @throws SocketException If connection cannot be established
     * @throws SmtpException If SMTP handshake fails
     * @throws ErrorException If TLS negotiation fails
     */
    private function connect(): void
    {
        if ($this->connected) {
            return;
        }

        $this->openSocket();
        $this->performSmtpHandshake();
        $this->enableTlsIfConfigured();
        $this->authenticateIfConfigured();

        $this->connected = true;
    }

    /**
     * Open TCP socket connection to SMTP server
     *
     * @throws SocketException
     */
    private function openSocket(): void
    {
        $hostname = $this->secure ? 'ssl://' . $this->hostname : $this->hostname;

        $errno = 0;
        $errstr = '';

        $socket = @fsockopen(
            $hostname,
            $this->port,
            $errno,
            $errstr,
            $this->timeout
        );

        if ($socket === false) {
            throw new SocketException(
                sprintf(
                    'Cannot connect to SMTP server %s:%d - %s (%d)',
                    $this->hostname,
                    $this->port,
                    $errstr,
                    $errno
                ),
                E_USER_ERROR
            );
        }

        $this->socket = $socket;
        stream_set_timeout($this->socket, $this->timeout);
    }

    /**
     * Perform SMTP handshake (EHLO/HELO)
     *
     * @throws SmtpException
     */
    private function performSmtpHandshake(): void
    {
        $code = $this->readResponse();

        if ($code !== self::SMTP_READY) {
            throw new SmtpException('SMTP server not ready: ' . $code);
        }

        $clientHostname = $this->getClientHostname();

        try {
            $this->executeCommand('EHLO ' . $clientHostname, self::SMTP_OK);
        } catch (SmtpException $e) {
            // Fallback to HELO if EHLO fails
            $this->executeCommand('HELO ' . $clientHostname, self::SMTP_OK);
        }
    }

    /**
     * Get client hostname for EHLO/HELO command
     *
     * @return string
     */
    private function getClientHostname(): string
    {
        if (isset($_SERVER['HTTP_HOST']) && preg_match('/^[\w.-]+\z/', $_SERVER['HTTP_HOST'])) {
            return $_SERVER['HTTP_HOST'];
        }

        return gethostname() ?: 'localhost';
    }

    /**
     * Enable TLS encryption if configured
     *
     * @throws ErrorException If TLS negotiation fails
     * @throws SmtpException
     */
    private function enableTlsIfConfigured(): void
    {
        if (!$this->tls) {
            return;
        }

        $this->executeCommand('STARTTLS', self::SMTP_READY);

        $secured = @stream_socket_enable_crypto(
            $this->socket,
            true,
            STREAM_CRYPTO_METHOD_TLS_CLIENT
        );

        if (!$secured) {
            throw new ErrorException(
                'Failed to enable TLS encryption on SMTP connection',
                E_ERROR
            );
        }

        // Re-send EHLO after STARTTLS
        $clientHostname = $this->getClientHostname();
        $this->executeCommand('EHLO ' . $clientHostname, self::SMTP_OK);
    }

    /**
     * Authenticate with SMTP server if credentials provided
     *
     * @throws SmtpException
     */
    private function authenticateIfConfigured(): void
    {
        if ($this->username === null || $this->password === null) {
            return;
        }

        $this->executeCommand('AUTH LOGIN', self::SMTP_AUTH_CONTINUE);
        $this->executeCommand(
            base64_encode($this->username),
            self::SMTP_AUTH_CONTINUE,
            'username'
        );
        $this->executeCommand(
            base64_encode($this->password),
            self::SMTP_AUTH_SUCCESS,
            'password'
        );
    }


    /**
     * Read SMTP server response code
     *
     * @return int Response code
     */
    private function readResponse(): int
    {
        $code = null;

        while (!feof($this->socket)) {
            $line = fgets($this->socket, 1000);

            if ($line === false) {
                continue;
            }

            $parts = explode(' ', trim($line));
            $code = $parts[0] ?? null;

            if ($code !== null && preg_match('/^\d{3}$/', $code)) {
                break;
            }
        }

        return (int)$code;
    }

    /**
     * Execute SMTP command and verify response
     *
     * @param string $command SMTP command to execute
     * @param int|array $expectedCode Expected response code(s)
     * @param string|null $label Command label for error messages
     * @return int Actual response code
     * @throws SmtpException If response code doesn't match expected
     */
    private function executeCommand(string $command, int|array $expectedCode, ?string $label = null): int
    {
        $this->writeToSocket($command . Envelop::END);

        $responseCode = $this->readResponse();

        $expectedCodes = (array)$expectedCode;

        if (!in_array($responseCode, $expectedCodes, true)) {
            $commandLabel = $label ?? $command;
            throw new SmtpException(
                sprintf(
                    'SMTP server did not accept %s with code [%s]',
                    $commandLabel,
                    $responseCode
                ),
                E_ERROR
            );
        }

        return $responseCode;
    }

    /**
     * Write data to socket
     *
     * @param string $data Data to write
     * @throws SmtpException If write fails
     */
    private function writeToSocket(string $data): void
    {
        if ($this->socket === null) {
            throw new SmtpException('Socket not connected');
        }

        $written = fwrite($this->socket, $data, strlen($data));

        if ($written === false) {
            throw new SmtpException('Failed to write to SMTP socket');
        }
    }

    /**
     * Close SMTP connection gracefully
     *
     * @return void
     */
    private function disconnect(): void
    {
        if (!$this->connected || $this->socket === null) {
            return;
        }

        try {
            $this->executeCommand('QUIT', self::SMTP_QUIT);
        } catch (SmtpException $e) {
            // Ignore errors during disconnect
            error_log('SMTP disconnect error: ' . $e->getMessage());
        } finally {
            if (is_resource($this->socket)) {
                fclose($this->socket);
            }

            $this->socket = null;
            $this->connected = false;
        }
    }

    /**
     * Destructor - ensure connection is closed
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
