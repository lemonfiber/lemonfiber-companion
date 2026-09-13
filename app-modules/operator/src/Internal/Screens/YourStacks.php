<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;
use Modules\Kernel\Api\Configured;
use Modules\Kernel\Api\Stacks;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * The screen a launch lands on: the machines this device knows, or an
 * invitation to introduce one.
 *
 * **Two states, one screen, because `/` is one route.** `N1-R35` is the empty
 * one and `N1-R36` is the other, and a screen that answered only the first is
 * what this was until pairing existed — it told an operator who had just paired
 * a stack that nothing was paired. That is the failure mode of a screen named
 * for one of its states: the name stops being a description and starts being a
 * claim, and nothing checks a claim in a class name.
 *
 * `N1-R35` refuses the obvious thing for the empty case — an empty operator
 * surface with a button somewhere in it. On a launch with no stack configured
 * the app has to say that setup happens at the machine, and `N1-R4` adds *and
 * why*, rather than omitting it silently. Somebody who installed a companion
 * app reasonably expects to set the thing up from their phone, and a screen
 * that simply offers nothing teaches them the app is broken.
 *
 * **`N1-R15` is why a row is a name and nothing else.** A stack's address is
 * the one thing on it that must never reach a screen, and a list is exactly
 * where somebody would put it to tell two entries apart. `N1-R11` already says
 * what tells them apart: the name the operator chose, which is the only part of
 * a stack they picked.
 *
 * **It reads no stack.** `N1-R36` asks for a usable frame without waiting for a
 * reading, and the cheapest way to keep that is to have nothing to wait for:
 * what this shows is retained configuration, which is on the device. Reaching
 * a stack belongs to the screen for one stack, which is a different screen and
 * needs a session this app does not have yet.
 *
 * **It carries `#[Lazy]`, and the reason it does is worth keeping.** This file
 * used to say the attribute was unnecessary because the screen reached no
 * port. It reaches one now, and the first instinct was that `F4` still did not
 * apply — `Stacks::configured()` reads this device's own retained state rather
 * than a machine over a network, so where is the wait? In the platform store.
 * A keychain read is a call across a process boundary, and on Android it can
 * be a binder call that waits on a locked store. `N1-R36` says a launch
 * reaches a usable frame without waiting for a reading, and *usually instant*
 * is not the same claim.
 *
 * The arch rule caught that, which is the argument for it being a rule rather
 * than a judgement: the assumption was made in good faith and was wrong about
 * the case that matters — a cold start on a device that has just been powered
 * on.
 *
 * **It answers for the case where there is nothing, and steps aside otherwise.**
 * `N1-R35` is about a launch with no stack configured; a launch *with* one must
 * not land here, and until something asked, every launch did. So this asks —
 * which is also why it has a constructor at all, and why {@see
 * \Bootstrap\Composition\NativePHP\ScreenRouter} exists: NativePHP builds a screen with
 * `new`, and a screen that reached the container itself is the service location
 * `A3` refuses.
 *
 * `Internal` rather than `Api` because nothing outside this module names it —
 * the surface's own provider declares it, and E2's promise is that anything in
 * here can be renamed without reading another module.
 */
#[Lazy]
final class YourStacks extends NativeComponent
{
    public function __construct(private readonly Stacks $stacks) {}

    /**
     * Whether this device has been introduced to anything.
     *
     * Read here rather than held as state, because a screen returned to after
     * a pairing would otherwise still be answering with what it knew when it
     * was built. `N1-R38` keeps what the *operator* did on a screen; what the
     * device is configured for is not that.
     */
    public function nothingIsPairedYet(): bool
    {
        return $this->configured()->isEmpty();
    }

    /**
     * The machines this device knows, in the order they were paired.
     *
     * Answers {@see Configured} rather than a list of names, which is what
     * keeps `N1-R11`'s guarantees with the value: it holds no current stack,
     * refuses one it does not know, and collapses a repeated identifier to the
     * later entry. A list of strings would leave each of those with the
     * template.
     *
     * Asked once per frame, like {@see nothingIsPairedYet()} and for the same
     * reason. A screen returned to after a pairing that held its list would
     * show the one it was built with.
     */
    public function configured(): Configured
    {
        return $this->stacks->configured();
    }

    /**
     * The frame, by name.
     *
     * A `View` rather than an `Element`: the base class accepts either, and a
     * Blade file is the half of a screen `tests/Templates` can read. An element
     * tree assembled in PHP would be invisible to every rule in that suite —
     * `F3`'s vocabulary check, `F5`'s screen-reader check and `L1`'s refusal of
     * an English sentence all work over the text of a template.
     */
    public function render(): View
    {
        return view('operator::your-stacks');
    }
}
