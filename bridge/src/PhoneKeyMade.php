<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

use Closure;

/**
 * What came back from asking the phone to make its key: the public half and the platform's attestation, or why not.
 *
 * Android attests with a chain of certificates and iOS with one App Attest object, so a key that was
 * made is one of two shapes, and {@see either()} has an arm for each.
 */
final readonly class PhoneKeyMade
{
    /**
     * @param list<string> $chain
     */
    private function __construct(
        private string $lives,
        private string $publicKey,
        private array $chain,
        private string $attestation,
        private ?WhyNoPhoneKey $why,
    ) {}

    /**
     * Made on Android: where it lives, its public half's DER and the attestation chain, the key's own first.
     *
     * @param list<string> $chain
     */
    public static function onAndroid(string $lives, string $publicKey, array $chain): self
    {
        return new self($lives, $publicKey, $chain, '', null);
    }

    /** Made on iOS: where it lives, its public half's DER and the App Attest object. */
    public static function onIos(string $lives, string $publicKey, string $attestation): self
    {
        return new self($lives, $publicKey, [], $attestation, null);
    }

    /** Not made, for this reason. */
    public static function not(WhyNoPhoneKey $why): self
    {
        return new self('', '', [], '', $why);
    }

    /**
     * Say what happens for each, and get back what you built.
     *
     * @template T
     *
     * @param Closure(string, string, list<string>): T $android where it lives, the public half, the chain
     * @param Closure(string, string, string): T       $ios     where it lives, the public half, the object
     * @param Closure(WhyNoPhoneKey): T                $not
     *
     * @return T
     */
    public function either(Closure $android, Closure $ios, Closure $not): mixed
    {
        return match (true) {
            $this->why instanceof WhyNoPhoneKey => $not($this->why),
            $this->chain !== [] => $android($this->lives, $this->publicKey, $this->chain),
            default => $ios($this->lives, $this->publicKey, $this->attestation),
        };
    }
}
