<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

use function trim;

/**
 * What a walkthrough walked, as the stack names it, or that it never got as far as choosing.
 *
 * Its own type rather than a nullable title, so a screen cannot draw a title
 * without saying what it draws where there is none.
 */
final readonly class WhatWasWalked
{
    private function __construct(private ?string $item) {}

    /** The title the stack says it walked, kept as said; a blank one is refused. */
    public static function called(string $item): self
    {
        if (trim($item) === '') {
            throw TheWalkthroughSaysNothing::about('item');
        }

        return new self($item);
    }

    /** It never got as far as choosing anything. */
    public static function nothingChosen(): self
    {
        return new self(null);
    }

    /**
     * Say what happens for a title and for none, and get back what you built.
     *
     * @template T of object
     *
     * @param Closure(string): T $named
     * @param Closure(): T       $nothingChosen
     *
     * @return T
     */
    public function either(Closure $named, Closure $nothingChosen): object
    {
        return $this->item === null ? $nothingChosen() : $named($this->item);
    }
}
