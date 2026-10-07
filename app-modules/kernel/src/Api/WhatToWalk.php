<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * What a walkthrough is asked to fetch: a title, or whatever is likely to work.
 *
 * Leaving it blank is an answer rather than a mistake. A first attempt that
 * fails because somebody picked something obscure teaches the wrong lesson,
 * so the stack is then left to choose something well seeded, and says what it
 * chose.
 */
final readonly class WhatToWalk implements AnAction
{
    private function __construct(private ?string $item) {}

    /** What was typed; blank or only spaces leaves the choice to the stack. */
    public static function called(string $item): self
    {
        $trimmed = trim($item);

        return new self($trimmed === '' ? null : $trimmed);
    }

    /**
     * The action's name, for a caller that has to name it before there is one
     * to ask for: what a stack says it serves is asked by name before a button
     * is drawn.
     */
    public static function named(): string
    {
        return 'walkthrough';
    }

    /**
     * The name lemonfiber's surface asks for this by.
     *
     * Spelled here, once, for {@see TakingAnUpdate::asked()}'s reason: an
     * adapter that spelled it could spell any action a stack offers.
     */
    public function asked(): string
    {
        return self::named();
    }

    /**
     * Say what happens for a title and for no title, and get back what you built.
     *
     * @template T of object
     *
     * @param Closure(string): T $named
     * @param Closure(): T       $likeliest
     *
     * @return T
     */
    public function either(Closure $named, Closure $likeliest): object
    {
        return $this->item === null ? $likeliest() : $named($this->item);
    }
}
