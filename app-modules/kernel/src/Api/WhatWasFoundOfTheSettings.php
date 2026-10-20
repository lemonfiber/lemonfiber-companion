<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * Every setting and secret a stack's services read, or the reason that could not be learned.
 */
final readonly class WhatWasFoundOfTheSettings
{
    private function __construct(private TheServicesSettings|Obstacle $answer) {}

    /** The stack answered with these. */
    public static function found(TheServicesSettings $settings): self
    {
        return new self($settings);
    }

    /** It did not, and this is what the operator met instead. */
    public static function met(Obstacle $why): self
    {
        return new self($why);
    }

    /**
     * Say what happens in each case, and get back what you built.
     *
     * @template T of object
     *
     * @param Closure(TheServicesSettings): T $found
     * @param Closure(Obstacle): T            $met
     *
     * @return T
     */
    public function either(Closure $found, Closure $met): object
    {
        return $this->answer instanceof Obstacle ? $met($this->answer) : $found($this->answer);
    }
}
