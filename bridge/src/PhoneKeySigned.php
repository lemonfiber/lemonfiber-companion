<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

use Closure;

/** What came back from asking the phone's key to sign: the signature's DER, or why there is none. */
final readonly class PhoneKeySigned
{
    /** @param string|WhyNothingWasSigned $signed the signature, or why there is none */
    private function __construct(private string|WhyNothingWasSigned $signed) {}

    /** Signed, and this is the signature. */
    public static function signed(string $signature): self
    {
        return new self($signature);
    }

    /** Nothing was signed, for this reason. */
    public static function not(WhyNothingWasSigned $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens for each, and get back what you built.
     *
     * @template T
     *
     * @param Closure(string): T              $signed
     * @param Closure(WhyNothingWasSigned): T $not
     *
     * @return T
     */
    public function either(Closure $signed, Closure $not): mixed
    {
        return $this->signed instanceof WhyNothingWasSigned ? $not($this->signed) : $signed($this->signed);
    }
}
