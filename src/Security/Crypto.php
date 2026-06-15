<?php

declare(strict_types=1);

namespace Bow\Security;

use RuntimeException;

class Crypto
{
    /**
     * The security key
     *
     * @var ?string
     */
    private static ?string $key = null;

    /**
     * The security cipher
     *
     * @var string
     */
    private static string $cipher = 'AES-256-CBC';

    /**
     * Header tagging the authenticated (random-IV + HMAC) payload format.
     *
     * The ':' is not part of the base64 alphabet, so a value carrying this
     * prefix can never be confused with a legacy (base64-only) ciphertext.
     */
    private const HEADER = 'BOW2:';

    /**
     * The authentication tag length in bytes (HMAC-SHA256).
     */
    private const MAC_LENGTH = 32;

    /**
     * Set the key
     *
     * @param string      $key
     * @param string|null $cipher
     */
    public static function setKey(string $key, ?string $cipher = null): void
    {
        static::$key = $key;

        if (!is_null($cipher)) {
            static::$cipher = $cipher;
        }
    }

    /**
     * Encrypt data.
     *
     * Produces an authenticated payload: a fresh random IV is used for every
     * call (so identical plaintexts yield different ciphertexts) and an
     * encrypt-then-MAC HMAC-SHA256 tag protects against tampering.
     *
     * @param  string $data
     * @return string
     */
    public static function encrypt(string $data): string
    {
        $key = static::resolveKey();

        $iv_size = (int) openssl_cipher_iv_length(static::$cipher);
        $iv = random_bytes($iv_size);

        $cipher_text = openssl_encrypt(
            $data,
            static::$cipher,
            static::deriveKey('enc', $key),
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($cipher_text === false) {
            throw new RuntimeException('Unable to encrypt the given data.');
        }

        $mac = hash_hmac('sha256', $iv . $cipher_text, static::deriveKey('auth', $key), true);

        return self::HEADER . base64_encode($iv . $mac . $cipher_text);
    }

    /**
     * Decrypt data.
     *
     * Authenticated payloads are verified before decryption and fail closed
     * (return false) on a bad tag, truncation or wrong key. Values produced by
     * the previous unauthenticated format are still readable for backward
     * compatibility.
     *
     * @param string $data
     *
     * @return string|bool
     */
    public static function decrypt(string $data): string|bool
    {
        $key = static::resolveKey();

        if (!str_starts_with($data, self::HEADER)) {
            return static::decryptLegacy($data, $key);
        }

        $raw = base64_decode(substr($data, strlen(self::HEADER)), true);

        if ($raw === false) {
            return false;
        }

        $iv_size = (int) openssl_cipher_iv_length(static::$cipher);

        if (strlen($raw) <= $iv_size + self::MAC_LENGTH) {
            return false;
        }

        $iv = substr($raw, 0, $iv_size);
        $mac = substr($raw, $iv_size, self::MAC_LENGTH);
        $cipher_text = substr($raw, $iv_size + self::MAC_LENGTH);

        $calculated = hash_hmac('sha256', $iv . $cipher_text, static::deriveKey('auth', $key), true);

        // Reject tampered or wrong-key payloads before touching the cipher.
        if (!hash_equals($calculated, $mac)) {
            return false;
        }

        return openssl_decrypt(
            $cipher_text,
            static::$cipher,
            static::deriveKey('enc', $key),
            OPENSSL_RAW_DATA,
            $iv
        );
    }

    /**
     * Decrypt a value produced by the legacy (static IV, unauthenticated)
     * format. Kept only so data encrypted before the upgrade keeps working.
     *
     * @param  string $data
     * @param  string $key
     * @return string|bool
     */
    private static function decryptLegacy(string $data, string $key): string|bool
    {
        $iv_size = (int) openssl_cipher_iv_length(static::$cipher);

        $iv = substr(sha1($key), 0, $iv_size);

        return openssl_decrypt($data, static::$cipher, $key, 0, $iv);
    }

    /**
     * Derive a purpose-specific 256-bit subkey from the configured key.
     *
     * Separating the encryption key from the authentication key (domain
     * separation) is required for encrypt-then-MAC to be sound, and normalises
     * an arbitrary-length configured key to a fixed strong key.
     *
     * @param  string $context
     * @param  string $key
     * @return string
     */
    private static function deriveKey(string $context, string $key): string
    {
        return hash_hmac('sha256', 'BowCrypto|v2|' . $context, $key, true);
    }

    /**
     * Resolve the configured key or fail loudly when it is missing.
     *
     * @return string
     */
    private static function resolveKey(): string
    {
        if (static::$key === null || static::$key === '') {
            throw new RuntimeException(
                'The application security key is not set. Define security.key before using Crypto.'
            );
        }

        return static::$key;
    }
}
