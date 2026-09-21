<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What a stack said it is set to, or what stood in the way of asking.
 *
 * **Told with nothing is not the same as not told.** A stack holding no
 * settings answers with an empty listing, and a stack that is asleep or that
 * declines answers with neither. Both draw as a blank screen unless the type
 * keeps them apart, and the difference is the whole of what the operator needs:
 * one says there is nothing set, the other says nobody asked successfully.
 *
 * Shaped after {@see WhatTheyAreOwed} because it is the same shape — a reading
 * that the world may decline to give — and a second spelling of it would be a
 * second set of arms for a template to get wrong.
 */
final readonly class HowItIsSet
{
    private function __construct(private Settings $set, private ?Obstacle $why) {}

    /**
     * The stack answered, and this is what it is set to.
     *
     * An empty {@see Settings} is an answer. A screen says so in as many words
     * rather than by drawing nothing.
     */
    public static function told(Settings $set): self
    {
        return new self(set: $set, why: null);
    }

    /**
     * The stack would not say, and this is what stood in the way.
     */
    public static function refused(Obstacle $why): self
    {
        return new self(set: Settings::none(), why: $why);
    }

    /**
     * Say what happens either way, and get back what you built.
     *
     * @template TTold of object
     * @template TRefused of object
     *
     * @param  Closure(Settings): TTold  $told
     * @param  Closure(Obstacle): TRefused  $refused
     * @return TTold|TRefused
     */
    public function either(Closure $told, Closure $refused): object
    {
        // Read off the refusal, as the rest of these do.
        return $this->why instanceof Obstacle ? $refused($this->why) : $told($this->set);
    }
}
