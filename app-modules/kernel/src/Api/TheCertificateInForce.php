<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Which internal certificate is in force, or that the stack did not say.
 *
 * Unsaid is its own case rather than a null: a credential that is not the
 * internal certificate, and a stack that listed it without its SHA-256, both
 * have nothing to show, and the screen shows nothing for either.
 */
final readonly class TheCertificateInForce
{
    private function __construct(private ?AnInternalCertificate $certificate) {}

    /** This certificate is in force. */
    public static function of(AnInternalCertificate $certificate): self
    {
        return new self($certificate);
    }

    /** The stack did not say which. */
    public static function unsaid(): self
    {
        return new self(null);
    }

    /** Whether the stack said which. */
    public function wasSaid(): bool
    {
        return $this->certificate instanceof AnInternalCertificate;
    }

    /** The one in force at a glance, or empty where the stack did not say which. */
    public function atAGlance(): string
    {
        return $this->certificate instanceof AnInternalCertificate ? $this->certificate->atAGlance()->shown() : '';
    }

    /** Whether that certificate is the one in force. */
    public function is(AnInternalCertificate $certificate): bool
    {
        return $this->certificate instanceof AnInternalCertificate && $this->certificate->is($certificate);
    }
}
