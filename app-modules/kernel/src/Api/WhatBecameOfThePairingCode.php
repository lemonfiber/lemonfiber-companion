<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * Where making a pairing code has got to: underway, made, gone, refused, or not reached.
 *
 * Shaped as {@see WhatBecameOfTheInvitation} is, for its reasons. A stack that
 * turns the asking down — it is not served encrypted on the network, it has no
 * address a phone could reach — says why in its own words, and that sentence
 * is the operator's answer rather than an obstacle.
 */
final readonly class WhatBecameOfThePairingCode
{
    /**
     * `because` is filled on the refused arm and on no other, which is what
     * tells a refusal from the stack having no outcome: both hold no answer.
     */
    private function __construct(private Job|APairingCode|Obstacle|null $answer, private string $because = '') {}

    /** The stack took it on, and this is what to ask after it by. */
    public static function underway(Job $job): self
    {
        return new self($job);
    }

    /** The stack made it, and this is the code. */
    public static function made(APairingCode $code): self
    {
        return new self($code);
    }

    /** The stack has no outcome for it any more. */
    public static function ended(): self
    {
        return new self(null);
    }

    /** The stack refused, in its own words; a blank reason is refused. */
    public static function refused(string $because): self
    {
        if (trim($because) === '') {
            throw PairingIsNotReadable::becauseItSaysNothingAbout('reason');
        }

        return new self(null, $because);
    }

    /** The stack was not reached, or not understood, and this is what the operator met. */
    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template TUnderway of object
     * @template TMade of object
     * @template TEnded of object
     * @template TRefused of object
     * @template TMet of object
     *
     * @param Closure(Job): TUnderway      $underway
     * @param Closure(APairingCode): TMade $made
     * @param Closure(): TEnded            $ended
     * @param Closure(string): TRefused    $refused
     * @param Closure(Obstacle): TMet      $met
     *
     * @return TUnderway|TMade|TEnded|TRefused|TMet
     */
    public function either(Closure $underway, Closure $made, Closure $ended, Closure $refused, Closure $met): object
    {
        return match (true) {
            $this->answer instanceof Job => $underway($this->answer),
            $this->answer instanceof APairingCode => $made($this->answer),
            $this->answer instanceof Obstacle => $met($this->answer),
            $this->because !== '' => $refused($this->because),
            default => $ended(),
        };
    }
}
