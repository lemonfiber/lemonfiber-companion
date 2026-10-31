<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The certificate the media server presents at its internal TLS port, by the SHA-256 of the one in force.
 *
 * Held apart from {@see Fingerprint} so it can never be pinned: the digest is
 * kept inside and only its {@see AtAGlance} form leaves, which is all the
 * credentials screen shows. It is not the certificate this app pinned for the
 * stack, and replacing it does not touch that pin.
 */
final readonly class AnInternalCertificate
{
    /** The setting the stack lists it under, on every surface. */
    public const string SETTING = 'internal-certificate';

    private function __construct(private Fingerprint $digest) {}

    /** The certificate by its SHA-256, refused where that is not sixty-four hexadecimal characters. */
    public static function of(string $digest): self
    {
        return new self(Fingerprint::of($digest));
    }

    /** The digest in the short form a person compares at a glance. */
    public function atAGlance(): AtAGlance
    {
        return AtAGlance::of($this->digest);
    }

    /** Whether this is the same certificate. */
    public function is(self $other): bool
    {
        return $this->digest->is($other->digest);
    }
}
