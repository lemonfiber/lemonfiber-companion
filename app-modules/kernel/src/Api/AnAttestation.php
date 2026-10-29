<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_values;

use Closure;

use function is_string;

/**
 * What the phone's platform says about where a key lives, for the stack to check.
 *
 * Android's hardware attests with a chain of certificates, the key's own first; Apple's App Attest
 * with one attestation object.
 */
final readonly class AnAttestation
{
    /**
     * @param list<string>|string $attested Android's chain, or Apple's object
     */
    private function __construct(private array|string $attested) {}

    /** Android's chain, each certificate in DER: the key's own, then each that vouches for the one before. */
    public static function fromAndroid(string $leaf, string ...$rest): self
    {
        return new self([$leaf, ...array_values($rest)]);
    }

    /** Apple's App Attest object, as App Attest wrote it. */
    public static function fromApple(string $object): self
    {
        return new self($object);
    }

    /**
     * Say what happens for each platform, and get back what you built.
     *
     * @template TAndroid of object
     * @template TApple of object
     *
     * @param Closure(list<string>): TAndroid $android
     * @param Closure(string): TApple         $apple
     *
     * @return TAndroid|TApple
     */
    public function either(Closure $android, Closure $apple): object
    {
        $attested = $this->attested;

        return is_string($attested) ? $apple($attested) : $android($attested);
    }
}
