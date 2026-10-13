<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use function chunk_split;
use function is_array;
use function is_string;

use const OPENSSL_ALGO_SHA256;
use const OPENSSL_KEYTYPE_EC;

use function openssl_pkey_get_details;
use function openssl_pkey_new;
use function openssl_sign;
use function openssl_verify;

use OpenSSLAsymmetricKey;

use function preg_replace;

use RuntimeException;

use function sodium_base642bin;

use const SODIUM_BASE64_VARIANT_ORIGINAL;

use function sodium_bin2base64;
use function sprintf;

/**
 * A P-256 key pair made by OpenSSL, signing the way a phone's hardware key does.
 *
 * ECDSA over SHA-256 in DER, with the public half as a `SubjectPublicKeyInfo` in DER: the shapes the
 * stack verifies, so a signature a stand-in answers with is one a test can check for real.
 */
final readonly class AKeyPairInSoftware
{
    private function __construct(private OpenSSLAsymmetricKey $private, private string $publicPem) {}

    /** A new pair. */
    public static function made(): self
    {
        $private = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);

        if (! $private instanceof OpenSSLAsymmetricKey) {
            throw new RuntimeException('OpenSSL made no P-256 key.');
        }

        $details = openssl_pkey_get_details($private);
        $pem = is_array($details) && is_string($details['key'] ?? null) ? $details['key'] : '';

        return new self($private, $pem);
    }

    /** Whether this signature is this pair's, over this text. */
    public static function verifies(string $publicKey, string $text, string $signature): bool
    {
        $pem = sprintf("-----BEGIN PUBLIC KEY-----\n%s-----END PUBLIC KEY-----\n", chunk_split(sodium_bin2base64($publicKey, SODIUM_BASE64_VARIANT_ORIGINAL), 64, "\n"));

        return openssl_verify($text, $signature, $pem, OPENSSL_ALGO_SHA256) === 1;
    }

    /** The public half's `SubjectPublicKeyInfo`, in DER. */
    public function publicKey(): string
    {
        return sodium_base642bin((string) preg_replace('/-----[A-Z ]+-----|\s/', '', $this->publicPem), SODIUM_BASE64_VARIANT_ORIGINAL);
    }

    /** ECDSA over SHA-256 of this text, in DER. */
    public function signed(string $text): string
    {
        $signature = '';
        openssl_sign($text, $signature, $this->private, OPENSSL_ALGO_SHA256);

        return is_string($signature) ? $signature : '';
    }
}
