<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * Where removing everything lemonfiber keeps has got to: underway, answered, ended, refused in the stack's words, or never reached.
 */
final readonly class WhatBecameOfForgetting
{
    private function __construct(private Job|TheForgetting|ARefusalInItsWords|Obstacle|null $answer) {}

    public static function underway(Job $job): self
    {
        return new self($job);
    }

    public static function answered(TheForgetting $forgetting): self
    {
        return new self($forgetting);
    }

    public static function ended(): self
    {
        return new self(null);
    }

    public static function refused(ARefusalInItsWords $why): self
    {
        return new self($why);
    }

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
     * @param Closure(TheForgetting): T      $answered
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
            $answer instanceof TheForgetting => $answered($answer),
            $answer instanceof ARefusalInItsWords => $refused($answer),
            $answer instanceof Obstacle => $met($answer),
            default => $ended(),
        };
    }
}
