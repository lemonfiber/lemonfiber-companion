<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use Modules\Kernel\Api\Configured;
use Modules\Kernel\Api\Remembered;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\WhyAStackCannotBeRemembered;

/**
 * The configured stacks, held for as long as a test runs.
 *
 * What every test that needs a paired device will reach for, which is why the
 * contract suite drives this and {@see \Modules\Vault\Api\PlatformStacks}
 * through the same expectations: a fake that is easier to satisfy than the
 * platform is a fake that quietly widens what the rest of the suite is written
 * against.
 *
 * Written by hand rather than mocked — `G1` — so a change to the port fails to
 * compile here rather than drifting.
 */
final class StacksInMemory implements Stacks
{
    private Configured $held;

    /** How many times something has read the list, for asserting an order. */
    private int $asked = 0;

    private function __construct(private readonly ?WhyAStackCannotBeRemembered $refusing)
    {
        $this->held = Configured::none();
    }

    /** A device that can remember a stack, and has not been asked to yet. */
    public static function working(): self
    {
        return new self(null);
    }

    /**
     * A device that already knows these, as a launch after pairing finds it.
     *
     * Built through `remember()` rather than by assigning the record, so a fake
     * cannot be put into a state the port could not produce — `Configured`
     * collapses a repeated identifier and keeps the order, and a test that
     * bypassed that would be testing a list this application never holds.
     */
    public static function holding(Stack ...$stacks): self
    {
        $device = new self(null);

        foreach ($stacks as $stack) {
            $device->remember($stack);
        }

        return $device;
    }

    /** A device that cannot write one down, and says which nothing stopped it. */
    public static function refusing(WhyAStackCannotBeRemembered $why): self
    {
        return new self($why);
    }

    public function configured(): Configured
    {
        $this->asked++;

        return $this->held;
    }

    /**
     * Whether anything is held, without counting as having read it.
     *
     * Deliberately not counted by {@see timesAsked()}. The count exists for the
     * one requirement about *order* — that a shut app has not read the
     * operator's machines — and asking whether the record is empty is not
     * reading them. Counting it here would make that rule refuse the
     * arrangement `N4-R22` asks for.
     */
    public function holdsAny(): bool
    {
        return ! $this->held->isEmpty();
    }

    /**
     * How many times the list has been read.
     *
     * For the one requirement that is about the *order* things are asked in
     * rather than the answer: `N4-R19` wants the device's own authentication on
     * a cold start, and a launch that read the stack list first would satisfy
     * every assertion about what it answered and still have looked at retained
     * state before the operator proved who they were.
     */
    public function timesAsked(): int
    {
        return $this->asked;
    }

    public function remember(Stack $stack): Remembered
    {
        if ($this->refusing instanceof WhyAStackCannotBeRemembered) {
            return Remembered::refused($this->refusing);
        }

        $this->held = $this->held->with($stack);

        return Remembered::safely();
    }
}
