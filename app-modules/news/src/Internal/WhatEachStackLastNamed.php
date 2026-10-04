<?php

declare(strict_types=1);

namespace Modules\News\Internal;

use function array_key_exists;

use Closure;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\TheNewestNamed;
use Modules\News\Api\HowMuchIsNew;

/**
 * What each stack last named as newest, and how much of it was new, held for the life of the process.
 *
 * So a screen that opens draws the marks and the count already heard for its
 * stack at once, rather than nothing until its own stream names the newest.
 * The runtime keeps one process alive across every screen, and the container
 * hands every screen the same one of these.
 *
 * **Held in memory only.** Nothing here is written, sealed or kept: it is gone
 * when the process is, and what the phone keeps of what is new is still the
 * one marker per kind {@see NewsOfAStack} keeps.
 *
 * **What was counted goes stale the moment what is kept changes.** Seeing an
 * item, seeing them all, and switching a kind off or on all go through
 * {@see NewsOfAStack::keep()}, which lets go of the count here, so the next
 * screen to ask counts again from what was named against what is kept now.
 * Removing a stack forgets its entry, and Clear saved data forgets every one.
 *
 * **Mutable, and it has to be.** It is what one screen hears and the next one
 * draws; `ModuleBoundariesTest` names it for that reason.
 */
final class WhatEachStackLastNamed
{
    /** @var array<string, TheNewestNamed> what each stack last named as newest, by its stored identifier */
    private array $named = [];

    /** @var array<string, HowMuchIsNew> how much of it was new, by its stored identifier, until what is kept changes */
    private array $counted = [];

    /** The stack named this as newest, and this much of it is new. */
    public function named(StackId $stack, TheNewestNamed $newest, HowMuchIsNew $counted): void
    {
        $this->named[$stack->stored()] = $newest;
        $this->counted[$stack->stored()] = $counted;
    }

    /**
     * How much was new when the stack last named the newest, counted again where what is kept changed since.
     *
     * Nothing where the stack has named nothing yet in this process.
     *
     * @param Closure(TheNewestNamed): HowMuchIsNew $counting
     */
    public function lastCounted(StackId $stack, Closure $counting): HowMuchIsNew
    {
        $which = $stack->stored();

        if (array_key_exists($which, $this->counted)) {
            return $this->counted[$which];
        }

        if (! array_key_exists($which, $this->named)) {
            return HowMuchIsNew::none();
        }

        $counted = $counting($this->named[$which]);
        $this->counted[$which] = $counted;

        return $counted;
    }

    /** What is kept of the stack's news changed, so what was counted is no longer known. */
    public function changed(StackId $stack): void
    {
        unset($this->counted[$stack->stored()]);
    }

    /** Forget everything held of the stack. */
    public function forget(StackId $stack): void
    {
        unset($this->named[$stack->stored()], $this->counted[$stack->stored()]);
    }

    /** Forget everything held of every stack. */
    public function forgetEverything(): void
    {
        $this->named = [];
        $this->counted = [];
    }
}
