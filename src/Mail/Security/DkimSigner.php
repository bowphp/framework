<?php

declare(strict_types=1);

namespace Bow\Mail\Security;

use Bow\Mail\Envelop;

class DkimSigner
{
    /**
     * DKIM configuration
     *
     * @var array
     */
    private array $config;

    /**
     * DkimSigner constructor
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Sign the email with DKIM
     *
     * @param Envelop $envelop
     * @return string
     */
    public function sign(Envelop $envelop): string
    {
        $privateKey = $this->loadPrivateKey();
        $headers = $this->getHeadersToSign($envelop);
        $bodyHash = $this->hashBody($envelop->getMessage());

        $stringToSign = $this->buildSignatureString($headers, $bodyHash);
        $signature = '';

        openssl_sign($stringToSign, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $signature = base64_encode($signature);

        return $this->buildDkimHeader($headers, $signature, $bodyHash);
    }

    /**
     * Load the private key
     *
     * @return mixed
     */
    private function loadPrivateKey()
    {
        $keyPath = $this->config['private_key'];
        $privateKey = file_get_contents($keyPath);
        return openssl_pkey_get_private($privateKey);
    }

    /**
     * Get headers to sign
     *
     * @param Envelop $envelop
     * @return array
     */
    private function getHeadersToSign(Envelop $envelop): array
    {
        $headers = [
            'From' => $envelop->getFrom(),
            'To' => $this->formatAddresses($envelop->getTo()),
            'Subject' => $envelop->getSubject(),
            'Date' => date('r'),
            'MIME-Version' => '1.0',
            'Content-Type' => $envelop->getType() . '; charset=' . $envelop->getCharset()
        ];

        return $headers;
    }

    /**
     * Format email addresses
     *
     * @param array $addresses
     * @return string
     */
    private function formatAddresses(array $addresses): string
    {
        return implode(', ', array_map(function ($address) {
            return $address[0] ? "{$address[0]} <{$address[1]}>" : $address[1];
        }, $addresses));
    }

    /**
     * Hash the message body
     *
     * @param string $body
     * @return string
     */
    private function hashBody(string $body): string
    {
        // Canonicalize body according to DKIM rules
        $body = preg_replace('/\r\n\s+/', ' ', $body);
        $body = trim($body) . "\r\n";

        return base64_encode(hash('sha256', $body, true));
    }

    /**
     * Build the string to sign
     *
     * @param array $headers
     * @param string $bodyHash
     * @return string
     */
    private function buildSignatureString(array $headers, string $bodyHash): string
    {
        $signedHeaderFields = array_keys($headers);
        $dkimHeaders = [];

        foreach ($signedHeaderFields as $field) {
            $dkimHeaders[] = strtolower($field) . ': ' . $headers[$field];
        }

        return implode("\r\n", $dkimHeaders) . "\r\n" . $bodyHash;
    }

    /**
     * Build the DKIM header
     *
     * @param array $headers
     * @param string $signature
     * @param string $bodyHash
     * @return string
     */
    private function buildDkimHeader(array $headers, string $signature, string $bodyHash): string
    {
        $domain = $this->config['domain'];
        $selector = $this->config['selector'];
        $signedHeaders = implode(':', array_map('strtolower', array_keys($headers)));

        return "DKIM-Signature: v=1; a=rsa-sha256; c=relaxed/relaxed; d={$domain}; s={$selector};\r\n" .
            "\tt=" . time() . "; bh={$bodyHash};\r\n" .
            "\th={$signedHeaders}; b={$signature};";
    }
}
