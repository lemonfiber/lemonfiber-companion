<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * Where asking after a hand-off has got to: underway, answered, gone, refused, or not reached.
 *
 * Shaped as {@see WhatBecameOfTheInvitation} is, for its reasons. A stack that
 * turns the asking down — no media server, the account that runs it — says why
 * in its own words, and that sentence is the operator's answer.
 */
final readonly class WhatBecameOfTheHandoff
{
    /**
     * `because` is filled on the refused arm and on no other, which is what
     * tells a refusal from the stack having no outcome: both hold no answer.
     */
    private function __construct(private Job|AHandoff|Obstacle|null $answer, private string $because = '') {}

    /** The stack took it on, and this is what to ask after it by. */
    public static function underway(Job $job): self
    {
        return new self($job);
    }

    /** The stack answered, and this is where it stands. */
    public static function answered(AHandoff $handoff): self
    {
        return new self($handoff);
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
            throw HandoffSaysNothing::about('reason');
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
     * @template TAnswered of object
     * @template TEnded of object
     * @template TRefused of object
     * @template TMet of object
     *
     * @param Closure(Job): TUnderway      $underway
     * @param Closure(AHandoff): TAnswered $answered
     * @param Closure(): TEnded            $ended
     * @param Closure(string): TRefused    $refused
     * @param Closure(Obstacle): TMet      $met
     *
     * @return TUnderway|TAnswered|TEnded|TRefused|TMet
     */
    public function either(Closure $underway, Closure $answered, Closure $ended, Closure $refused, Closure $met): object
    {
        return match (true) {
            $this->answer instanceof Job => $underway($this->answer),
            $this->answer instanceof AHandoff => $answered($this->answer),
            $this->answer instanceof Obstacle => $met($this->answer),
            $this->because !== '' => $refused($this->because),
            default => $ended(),
        };
    }
}
