<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * Where accepting a warning has got to: underway, answered, ended, refused in the stack's words, or never reached.
 */
final readonly class WhatBecameOfTheAcceptance
{
    private function __construct(private Job|Report|ARefusalInItsWords|Obstacle|null $answer) {}

    /** The stack took it on, and this is the handle to follow it by. */
    public static function underway(Job $job): self
    {
        return new self($job);
    }

    /** It finished, and this is the report the stack then gave, the accepted warning suppressed in it. */
    public static function answered(Report $report): self
    {
        return new self($report);
    }

    /** The stack has no outcome for it any more. */
    public static function ended(): self
    {
        return new self(null);
    }

    /** The stack refused it, in its own words. */
    public static function refused(ARefusalInItsWords $why): self
    {
        return new self($why);
    }

    /** The stack could not be reached, or would not admit the session. */
    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template T of object
     *
     * @param Closure(Job): T                $underway
     * @param Closure(Report): T             $answered
     * @param Closure(): T                   $ended
     * @param Closure(ARefusalInItsWords): T $refused
     * @param Closure(Obstacle): T           $met
     *
     * @return T
     */
    public function either(Closure $underway, Closure $answered, Closure $ended, Closure $refused, Closure $met): object
    {
        $answer = $this->answer;

        return match (true) {
            $answer instanceof Job => $underway($answer),
            $answer instanceof Report => $answered($answer),
            $answer instanceof ARefusalInItsWords => $refused($answer),
            $answer instanceof Obstacle => $met($answer),
            default => $ended(),
        };
    }
}
