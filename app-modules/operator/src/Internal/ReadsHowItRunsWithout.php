<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\HowItRunsWithoutLemonfiber;
use Modules\Kernel\Api\Session;
use Modules\Operator\Internal\Presenters\HowRunningWithoutReads;
use Modules\Operator\Internal\ViewModels\RunningWithoutAsShown;
use Native\Mobile\Edge\NativeComponent;

/**
 * A screen of a stack that also says how the stack runs without lemonfiber, read on a frame of its own.
 *
 * @phpstan-require-extends NativeComponent
 */
trait ReadsHowItRunsWithout
{
    /** What the stack said of running without lemonfiber, once a frame has read it. Public for {@see Screens\WhatKeepsRunningHere::$answered}'s reason. */
    public ?HowItRunsWithoutLemonfiber $heardWithout = null;

    /** What the screen drew of it, once a frame has read it. Public for the same reason. */
    public ?RunningWithoutAsShown $without = null;

    /** How the stack runs without lemonfiber, read on a frame of its own, or nothing while it waits. */
    public function without(): ?RunningWithoutAsShown
    {
        if ($this->without instanceof RunningWithoutAsShown || ! $this->mayReadItsStack()) {
            return $this->without;
        }

        $stack = $this->stack();

        return $this->without = $this->storage->resume($stack->id())->either(
            held: fn(Session $session): RunningWithoutAsShown => $this->askingWithout->withoutLemonfiber($stack, $session)->either(
                said: fn(HowItRunsWithoutLemonfiber $how): RunningWithoutAsShown => new HowRunningWithoutReads()->this($this->heardWithout = $how),
                met: $this->lettingGoIfRefused($stack, new HowRunningWithoutReads()->met(...)),
            ),
            notHeld: static fn(): RunningWithoutAsShown => new HowRunningWithoutReads()->signedOut(),
        );
    }

    /** Whether how it runs without lemonfiber waits for the next frame, this one having read the stack. */
    public function withoutIsBeingRead(): bool
    {
        return ! $this->without() instanceof RunningWithoutAsShown;
    }

    /** Let go of it, so the next frame reads it again. */
    private function forgetHowItRunsWithout(): void
    {
        $this->heardWithout = null;
        $this->without = null;
    }
}
