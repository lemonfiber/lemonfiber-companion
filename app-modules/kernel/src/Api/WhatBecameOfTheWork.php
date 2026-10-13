<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * Where work asked of a stack has got to: underway, answered, ended, refused in the stack's words, or never reached.
 *
 * One type for every act that reads these five ways; what differs is only what
 * the answered arm carries, and that is the type parameter: a port answers
 * `WhatBecameOfTheWork<Report>`, `<TheLineAsChanged>` or `<TheAllowanceAsChanged>`,
 * so the answered arm stays typed and a screen cannot read one report as another.
 *
 * @template-covariant TAnswer of object = never
 */
final readonly class WhatBecameOfTheWork
{
    /** @param TAnswer|null $answer */
    private function __construct(
        private ?Job $job = null,
        private ?object $answer = null,
        private ?ARefusalInItsWords $refused = null,
        private ?Obstacle $met = null,
    ) {}

    /**
     * The stack took it on, and this is the handle to follow it by.
     *
     * @return self<never>
     */
    public static function underway(Job $job): self
    {
        return new self(job: $job);
    }

    /**
     * It finished, and this is what the stack then said.
     *
     * @template TSaid of object
     *
     * @param TSaid $said
     *
     * @return self<TSaid>
     */
    public static function answered(object $said): self
    {
        return new self(answer: $said);
    }

    /**
     * The stack has no outcome for it any more.
     *
     * @return self<never>
     */
    public static function ended(): self
    {
        return new self();
    }

    /**
     * The stack refused it, in its own words.
     *
     * @return self<never>
     */
    public static function refused(ARefusalInItsWords $why): self
    {
        return new self(refused: $why);
    }

    /**
     * The stack could not be reached, or would not admit the session.
     *
     * @return self<never>
     */
    public static function met(Obstacle $why): self
    {
        return new self(met: $why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template T of object
     *
     * @param Closure(Job): T                $underway
     * @param Closure(TAnswer): T            $answered
     * @param Closure(): T                   $ended
     * @param Closure(ARefusalInItsWords): T $refused
     * @param Closure(Obstacle): T           $met
     *
     * @return T
     */
    public function either(Closure $underway, Closure $answered, Closure $ended, Closure $refused, Closure $met): object
    {
        return match (true) {
            $this->job instanceof Job => $underway($this->job),
            $this->answer !== null => $answered($this->answer),
            $this->refused instanceof ARefusalInItsWords => $refused($this->refused),
            $this->met instanceof Obstacle => $met($this->met),
            default => $ended(),
        };
    }
}
