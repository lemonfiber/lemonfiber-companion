<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use function array_key_exists;

use Modules\Kernel\Api\Showing;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\TheNewsOfAStack;
use Modules\Operator\Internal\TheNewsAsItems;

/**
 * What each stack listed when What's new last read it, and the stacks it could not reach.
 *
 * Held between frames, so a tap acts on the list the operator was looking at and
 * a frame that has read a stack does not read it again.
 */
final readonly class WhatEachStackListed
{
    /**
     * @param array<string, TheNewsOfAStack> $listed    what each stack listed, by its stored identifier
     * @param array<string, Showing>         $unreached what the phone last knew of each stack that could not be read, by its stored identifier
     */
    private function __construct(private array $listed, private array $unreached) {}

    /** Nothing read yet. */
    public static function nothingYet(): self
    {
        return new self([], []);
    }

    /** The same, with what one stack listed. */
    public function listing(StackId $stack, TheNewsOfAStack $news): self
    {
        return new self([...$this->listed, $stack->stored() => $news], $this->unreached);
    }

    /** The same, with one stack that could not be read and what the phone last knew of it. */
    public function unreached(StackId $stack, Showing $lastKnown): self
    {
        return new self($this->listed, [...$this->unreached, $stack->stored() => $lastKnown]);
    }

    /** Whether the stack has been read, or tried, since this began. */
    public function hasAsked(StackId $stack): bool
    {
        return array_key_exists($stack->stored(), $this->listed) || $this->couldNotReach($stack);
    }

    /** Whether the stack could not be read. */
    public function couldNotReach(StackId $stack): bool
    {
        return array_key_exists($stack->stored(), $this->unreached);
    }

    /** What the phone last knew of a stack that could not be read, or nothing where it was read or not yet tried. */
    public function lastKnownOf(StackId $stack): ?Showing
    {
        if (! array_key_exists($stack->stored(), $this->unreached)) {
            return null;
        }

        return $this->unreached[$stack->stored()];
    }

    /** What the stack listed, as items of news, or nothing where it has not been read. */
    public function of(StackId $stack): ?TheNewsAsItems
    {
        return array_key_exists($stack->stored(), $this->listed)
            ? new TheNewsAsItems($this->listed[$stack->stored()])
            : null;
    }
}
