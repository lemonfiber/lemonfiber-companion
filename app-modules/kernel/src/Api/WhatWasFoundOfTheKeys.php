<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * The integration keys a stack lists, a revoke it refused in its words, or the reason they could not be read or revoked.
 *
 * The answer {@see KeepingKeysForApps} gives to listing and to revoking alike:
 * a revoke answers with the listing as it stands after it.
 */
final readonly class WhatWasFoundOfTheKeys
{
    private function __construct(private TheKeysForApps|ARefusalInItsWords|Obstacle $answer) {}

    /** The stack answered, and these are its keys. */
    public static function found(TheKeysForApps $keys): self
    {
        return new self($keys);
    }

    /** The stack refused a revoke, in its own words. */
    public static function refused(ARefusalInItsWords $why): self
    {
        return new self($why);
    }

    /** It did not, and this is what the operator met instead. */
    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TFound of object
     * @template TRefused of object
     * @template TMet of object
     *
     * @param Closure(TheKeysForApps): TFound         $found
     * @param Closure(ARefusalInItsWords): TRefused   $refused
     * @param Closure(Obstacle): TMet                 $met
     *
     * @return TFound|TRefused|TMet
     */
    public function either(Closure $found, Closure $refused, Closure $met): object
    {
        return match (true) {
            $this->answer instanceof Obstacle => $met($this->answer),
            $this->answer instanceof ARefusalInItsWords => $refused($this->answer),
            default => $found($this->answer),
        };
    }
}
