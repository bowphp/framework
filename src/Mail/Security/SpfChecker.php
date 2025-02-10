<?php

declare(strict_types=1);

namespace Bow\Mail\Security;

class SpfChecker
{
    /**
     * SPF configuration
     *
     * @var array
     */
    private array $config;

    /**
     * SpfChecker constructor
     *
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
    }

    /**
     * Verify SPF record for a sender
     *
     * @param string $ip
     * @param string $sender
     * @param string $helo
     * @return string
     */
    public function verify(string $ip, string $sender, string $helo): string
    {
        $domain = $this->extractDomain($sender);
        $spfRecord = $this->getSpfRecord($domain);

        if (!$spfRecord) {
            return 'none';
        }

        $result = $this->evaluateSpf($spfRecord, $ip, $domain, $helo);

        return $result;
    }

    /**
     * Extract domain from email address
     *
     * @param string $email
     * @return string
     */
    private function extractDomain(string $email): string
    {
        return substr(strrchr($email, "@"), 1) ?: $email;
    }

    /**
     * Get SPF record for domain
     *
     * @param string $domain
     * @return string|null
     */
    private function getSpfRecord(string $domain): ?string
    {
        $records = dns_get_record($domain, DNS_TXT);

        foreach ($records as $record) {
            if (strpos($record['txt'] ?? '', 'v=spf1') === 0) {
                return $record['txt'];
            }
        }

        return null;
    }

    /**
     * Evaluate SPF record
     *
     * @param string $spfRecord
     * @param string $ip
     * @param string $domain
     * @param string $helo
     * @return string
     */
    private function evaluateSpf(string $spfRecord, string $ip, string $domain, string $helo): string
    {
        $mechanisms = explode(' ', $spfRecord);
        array_shift($mechanisms); // Remove v=spf1

        foreach ($mechanisms as $mechanism) {
            $result = $this->checkMechanism($mechanism, $ip, $domain, $helo);
            if ($result !== null) {
                return $result;
            }
        }

        return 'neutral';
    }

    /**
     * Check SPF mechanism
     *
     * @param string $mechanism
     * @param string $ip
     * @param string $domain
     * @param string $helo
     * @return string|null
     */
    private function checkMechanism(string $mechanism, string $ip, string $domain, string $helo): ?string
    {
        $qualifier = substr($mechanism, 0, 1);
        if (in_array($qualifier, ['+', '-', '~', '?'])) {
            $mechanism = substr($mechanism, 1);
        } else {
            $qualifier = '+';
        }

        if (str_starts_with($mechanism, 'ip4:')) {
            return $this->checkIp4($mechanism, $ip, $qualifier);
        }

        if (str_starts_with($mechanism, 'ip6:')) {
            return $this->checkIp6($mechanism, $ip, $qualifier);
        }

        if (str_starts_with($mechanism, 'a')) {
            return $this->checkA($mechanism, $ip, $domain, $qualifier);
        }

        if (str_starts_with($mechanism, 'mx')) {
            return $this->checkMx($mechanism, $ip, $domain, $qualifier);
        }

        if ($mechanism === 'all') {
            return $this->getQualifierResult($qualifier);
        }

        return null;
    }

    /**
     * Check IPv4 mechanism
     *
     * @param string $mechanism
     * @param string $ip
     * @param string $qualifier
     * @return string|null
     */
    private function checkIp4(string $mechanism, string $ip, string $qualifier): ?string
    {
        $range = substr($mechanism, 4);
        if ($this->ipInRange($ip, $range)) {
            return $this->getQualifierResult($qualifier);
        }
        return null;
    }

    /**
     * Check if IP is in range
     *
     * @param string $ip
     * @param string $range
     * @return bool
     */
    private function ipInRange(string $ip, string $range): bool
    {
        if (str_contains($range, '/')) {
            [$subnet, $bits] = explode('/', $range);
            $ip2long = ip2long($ip);
            $subnet2long = ip2long($subnet);
            $mask = -1 << (32 - $bits);
            return ($ip2long & $mask) === ($subnet2long & $mask);
        }
        return $ip === $range;
    }

    /**
     * Get result based on qualifier
     *
     * @param string $qualifier
     * @return string
     */
    private function getQualifierResult(string $qualifier): string
    {
        return match ($qualifier) {
            '+' => 'pass',
            '-' => 'fail',
            '~' => 'softfail',
            '?' => 'neutral',
            default => 'neutral'
        };
    }

    /**
     * Check IPv6 mechanism
     *
     * @param string $mechanism
     * @param string $ip
     * @param string $qualifier
     * @return string|null
     */
    private function checkIp6(string $mechanism, string $ip, string $qualifier): ?string
    {
        $range = substr($mechanism, 4);
        if ($this->ipInRange($ip, $range)) {
            return $this->getQualifierResult($qualifier);
        }
        return null;
    }

    /**
     * Check A record mechanism
     *
     * @param string $mechanism
     * @param string $ip
     * @param string $domain
     * @param string $qualifier
     * @return string|null
     */
    private function checkA(string $mechanism, string $ip, string $domain, string $qualifier): ?string
    {
        $records = dns_get_record($domain, DNS_A);
        foreach ($records as $record) {
            if ($record['ip'] === $ip) {
                return $this->getQualifierResult($qualifier);
            }
        }
        return null;
    }

    /**
     * Check MX record mechanism
     *
     * @param string $mechanism
     * @param string $ip
     * @param string $domain
     * @param string $qualifier
     * @return string|null
     */
    private function checkMx(string $mechanism, string $ip, string $domain, string $qualifier): ?string
    {
        $records = dns_get_record($domain, DNS_MX);
        foreach ($records as $record) {
            $aRecords = dns_get_record($record['target'], DNS_A);
            foreach ($aRecords as $aRecord) {
                if ($aRecord['ip'] === $ip) {
                    return $this->getQualifierResult($qualifier);
                }
            }
        }
        return null;
    }
}
