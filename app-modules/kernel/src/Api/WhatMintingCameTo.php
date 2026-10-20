<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What a stack answered a step of minting a key from this phone: its answer, that it knows no phone key by the
 * name this phone's is registered under, a refusal in its own words, or what stood in the way.
 *
 * A phone key the stack does not know was never registered, or was revoked; the phone then
 * throws its own away and registers a new one at the next mint.
 *
 * @template-covariant TAnswer of object = never
 */
final readonly class WhatMintingCameTo
{
    /** @param TAnswer|ARefusalInItsWords|Obstacle|null $answer null where the stack knows no such phone key */
    private function __construct(private ?object $answer) {}

    /**
     * The stack answered, and this is what it said.
     *
     * @template TSaid of object
     *
     * @param TSaid $said
     *
     * @return self<TSaid>
     */
    public static function answered(object $said): self
    {
        return new self($said);
    }

    /**
     * The stack knows no phone key by the name this phone's key is registered under.
     *
     * @return self<never>
     */
    public static function unregistered(): self
    {
        return new self(null);
    }

    /**
     * The stack refused it, in its own words.
     *
     * @return self<never>
     */
    public static function refused(ARefusalInItsWords $why): self
    {
        return new self($why);
    }

    /**
     * The stack could not be reached, or would not admit the session.
     *
     * @return self<never>
     */
    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template T of object
     *
     * @param Closure(TAnswer): T            $answered
     * @param Closure(): T                   $unregistered
     * @param Closure(ARefusalInItsWords): T $refused
     * @param Closure(Obstacle): T           $met
     *
     * @return T
     */
    public function either(Closure $answered, Closure $unregistered, Closure $refused, Closure $met): object
    {
        return match (true) {
            $this->answer === null => $unregistered(),
            $this->answer instanceof ARefusalInItsWords => $refused($this->answer),
            $this->answer instanceof Obstacle => $met($this->answer),
            default => $answered($this->answer),
        };
    }
}
