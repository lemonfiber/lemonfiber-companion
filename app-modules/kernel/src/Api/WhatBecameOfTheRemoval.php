<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * Where taking somebody out has got to: still going, answered, ended, refused, or never reached.
 *
 * Shaped as {@see WhatBecameOfTheInvitation} is, because the stack answers
 * this the same way: every act with work to follow, and the same four
 * outcomes once it is followed.
 *
 * **A refusal is the stack's answer, not a failure to reach it.** It carries
 * the stack's own sentence and has its own arm, so a screen draws it as the
 * reason it is rather than as something to try again. *Ended* is the stack
 * saying it has no outcome for that work any more, which is neither.
 */
final readonly class WhatBecameOfTheRemoval
{
    /**
     * `because` is filled on the refused arm and on no other, which is what
     * tells a refusal from the stack having no outcome: both hold no answer.
     */
    private function __construct(private Job|ARemoval|Obstacle|null $answer, private string $because = '') {}

    /** The stack took it on, and this is what to ask after it by. */
    public static function underway(Job $job): self
    {
        return new self($job);
    }

    /** The stack finished it, and this is what it came to. */
    public static function answered(ARemoval $removal): self
    {
        return new self($removal);
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
            throw RemovalSaysNothing::about('reason');
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
     * @param Closure(Job): TUnderway       $underway
     * @param Closure(ARemoval): TAnswered  $answered
     * @param Closure(): TEnded             $ended
     * @param Closure(string): TRefused     $refused
     * @param Closure(Obstacle): TMet       $met
     *
     * @return TUnderway|TAnswered|TEnded|TRefused|TMet
     */
    public function either(Closure $underway, Closure $answered, Closure $ended, Closure $refused, Closure $met): object
    {
        return match (true) {
            $this->answer instanceof Job => $underway($this->answer),
            $this->answer instanceof ARemoval => $answered($this->answer),
            $this->answer instanceof Obstacle => $met($this->answer),
            $this->because !== '' => $refused($this->because),
            default => $ended(),
        };
    }
}
