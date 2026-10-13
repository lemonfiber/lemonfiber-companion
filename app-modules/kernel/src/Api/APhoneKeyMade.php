<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/** The key this phone made for a stack, with what vouches for it, or why none was made. */
final readonly class APhoneKeyMade
{
    private function __construct(
        private WhereAPhoneKeyLives|WhyThisPhoneHoldsNoKey $where,
        private string $publicKey,
        private AnAttestation $attestation,
    ) {}

    /**
     * Made, and living there.
     *
     * @param string $publicKey the public half's `SubjectPublicKeyInfo`, in DER
     */
    public static function made(WhereAPhoneKeyLives $where, string $publicKey, AnAttestation $attestation): self
    {
        return new self($where, $publicKey, $attestation);
    }

    /** Not made, for this reason. */
    public static function not(WhyThisPhoneHoldsNoKey $why): self
    {
        return new self($why, '', AnAttestation::fromApple(''));
    }

    /**
     * Say what happens either way, and get back what you built.
     *
     * @template TMade of object
     * @template TNot of object
     *
     * @param Closure(WhereAPhoneKeyLives, string, AnAttestation): TMade $made where it lives, its public half's DER, what vouches for it
     * @param Closure(WhyThisPhoneHoldsNoKey): TNot                       $not
     *
     * @return TMade|TNot
     */
    public function either(Closure $made, Closure $not): object
    {
        if ($this->where instanceof WhyThisPhoneHoldsNoKey) {
            return $not($this->where);
        }

        return $made($this->where, $this->publicKey, $this->attestation);
    }
}
