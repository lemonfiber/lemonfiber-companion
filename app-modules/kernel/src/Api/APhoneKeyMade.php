<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/** The key this phone made for a stack, with what vouches for it, or why none was made. */
final readonly class APhoneKeyMade
{
    /** @param array{WhereAPhoneKeyLives, string, AnAttestation}|WhyThisPhoneHoldsNoKey $made where it lives, its public half's DER and what vouches for it; or why none was made */
    private function __construct(private array|WhyThisPhoneHoldsNoKey $made) {}

    /**
     * Made, and living there.
     *
     * @param string $publicKey the public half's `SubjectPublicKeyInfo`, in DER
     */
    public static function made(WhereAPhoneKeyLives $where, string $publicKey, AnAttestation $attestation): self
    {
        return new self([$where, $publicKey, $attestation]);
    }

    /** Not made, for this reason. */
    public static function not(WhyThisPhoneHoldsNoKey $why): self
    {
        return new self($why);
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
        return $this->made instanceof WhyThisPhoneHoldsNoKey ? $not($this->made) : $made(...$this->made);
    }
}
