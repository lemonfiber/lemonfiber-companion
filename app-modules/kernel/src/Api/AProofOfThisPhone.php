<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/** This phone's signature over a challenge, under the name its key is registered by. */
final readonly class AProofOfThisPhone
{
    private function __construct(private APhoneKeysName $phone, private AChallengeToSign $challenge, private string $signature) {}

    /** @param string $signature ECDSA over SHA-256, in DER */
    public static function of(APhoneKeysName $phone, AChallengeToSign $challenge, string $signature): self
    {
        return new self($phone, $challenge, $signature);
    }

    /** The name the phone's key is registered by. */
    public function phone(): APhoneKeysName
    {
        return $this->phone;
    }

    /** The challenge signed. */
    public function challenge(): AChallengeToSign
    {
        return $this->challenge;
    }

    /** The signature, in DER. */
    public function signature(): string
    {
        return $this->signature;
    }
}
