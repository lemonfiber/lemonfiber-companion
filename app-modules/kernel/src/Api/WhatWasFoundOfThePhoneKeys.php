<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * The phone keys a stack lists, or the reason they could not be read or revoked.
 *
 * The answer {@see KeepingPhoneKeys} gives to listing and to revoking alike:
 * a revoke answers with the listing as it stands after it.
 */
final readonly class WhatWasFoundOfThePhoneKeys
{
    private function __construct(private ThePhoneKeys|ARefusalInItsWords|Obstacle $answer) {}

    /** The stack answered, and these are its phone keys. */
    public static function found(ThePhoneKeys $keys): self
    {
        return new self($keys);
    }

    /** The stack refused, in its own words. */
    public static function refused(ARefusalInItsWords $why): self
    {
        return new self($why);
    }

    /** It could not be asked, and this is what the operator met instead. */
    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template T of object
     *
     * @param Closure(ThePhoneKeys): T       $found
     * @param Closure(ARefusalInItsWords): T $refused
     * @param Closure(Obstacle): T           $met
     *
     * @return T
     */
    public function either(Closure $found, Closure $refused, Closure $met): object
    {
        return match (true) {
            $this->answer instanceof ThePhoneKeys => $found($this->answer),
            $this->answer instanceof ARefusalInItsWords => $refused($this->answer),
            default => $met($this->answer),
        };
    }
}
