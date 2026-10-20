<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/** A key this phone made, as a stack registers it: its name, its public half and what vouches for it. */
final readonly class APhoneKeyToRegister
{
    private function __construct(private APhoneKeysName $name, private string $publicKey, private AnAttestation $attestation) {}

    /** @param string $publicKey the public half's `SubjectPublicKeyInfo`, in DER */
    public static function of(APhoneKeysName $name, string $publicKey, AnAttestation $attestation): self
    {
        return new self($name, $publicKey, $attestation);
    }

    /** The name it is registered under. */
    public function name(): APhoneKeysName
    {
        return $this->name;
    }

    /** Its public half's `SubjectPublicKeyInfo`, in DER. */
    public function publicKey(): string
    {
        return $this->publicKey;
    }

    /** What the phone's platform says about where it lives. */
    public function attestation(): AnAttestation
    {
        return $this->attestation;
    }
}
