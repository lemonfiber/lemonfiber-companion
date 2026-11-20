<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The certificate a stack's web surface presents, as the certificate describes itself. Never its key or its encoding.
 */
final readonly class TheCertificateItPresents
{
    private function __construct(
        private Fingerprint $fingerprint,
        private ACertificateName $subject,
        private ACertificateName $issuer,
        private bool $selfSigned,
        private TheNamesItCovers $names,
        private Instant $from,
        private Instant $until,
    ) {}

    /** What the stack said of it. */
    public static function said(
        Fingerprint $fingerprint,
        ACertificateName $subject,
        ACertificateName $issuer,
        bool $selfSigned,
        TheNamesItCovers $names,
        Instant $from,
        Instant $until,
    ): self {
        return new self($fingerprint, $subject, $issuer, $selfSigned, $names, $from, $until);
    }

    /** Its fingerprint, which a phone pins. */
    public function fingerprint(): Fingerprint
    {
        return $this->fingerprint;
    }

    /** Whom it was made out to. */
    public function subject(): ACertificateName
    {
        return $this->subject;
    }

    /** Who made it out. */
    public function issuer(): ACertificateName
    {
        return $this->issuer;
    }

    /** Whether it signed itself. */
    public function isSelfSigned(): bool
    {
        return $this->selfSigned;
    }

    /** The names it covers. */
    public function names(): TheNamesItCovers
    {
        return $this->names;
    }

    /** When it is valid from. */
    public function validFrom(): Instant
    {
        return $this->from;
    }

    /** When it is valid until. */
    public function validUntil(): Instant
    {
        return $this->until;
    }
}
