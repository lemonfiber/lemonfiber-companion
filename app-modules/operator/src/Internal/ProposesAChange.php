<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use function array_keys;

use Closure;
use Modules\Kernel\Api\Adjusting;
use Modules\Kernel\Api\ASettingChange;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheSettingAsChanged;
use Modules\Kernel\Api\WhatTheSettingRunDid;
use Modules\Kernel\Api\WhatTheStackMadeOfIt;
use Modules\Kernel\Api\WhatToSet;
use Modules\Kernel\Api\WhereTheChangeStands;
use Modules\Kernel\Api\WhetherToWait;
use Modules\Operator\Internal\Presenters\HowAChangeReads;
use Modules\Operator\Internal\ViewModels\WhatAChangeTurnedOutToBe;
use Native\Mobile\Attributes\Poll;

/**
 * Changing one setting through the stack's review: asked, agreed to, and followed while the downloads land.
 *
 * The write is the one the stack names for the key: `settings-set` under the
 * name the screen's `keptAs()` reads off the stack's entry, `config-set` where
 * it names none.
 */
trait ProposesAChange
{
    use PutsASettingChange;

    /**
     * What the operator has typed into the setting open for changing, while one is.
     *
     * `public`, as {@see PairByTyping}'s is and for its reason:
     * `NativeComponent::__syncProperty()` writes only public, non-static
     * properties, so a protected one is never assigned. Public mutable state is
     * what `enforceReadonlyPublicProperty` refuses, and `phpstan.neon` says why
     * a screen is the one place that rule cannot apply.
     *
     * Seeded from the value the stack showed when a setting is opened, so the
     * common edit — one character of a path — does not begin by retyping the
     * whole thing.
     */
    public ?string $typed = null;

    /** Which setting is open for changing, where one is. */
    public ?string $changing = null;

    /** Where the proposal stands, once one has been put to the stack. */
    public ?WhatAChangeTurnedOutToBe $proposed = null;

    /** The name of a change agreed to once the downloads land, while the stack waits for them. */
    public ?string $landing = null;

    /** The key for why a change agreed to once the downloads land was not made, where it was not. */
    public ?string $landingSaid = null;

    /** What the operator has typed into the setting open for changing, or nothing while none is. */
    public function typed(): ?string
    {
        return $this->typed;
    }

    /**
     * Which setting is open for changing, or nothing.
     *
     * A string rather than the row, because the template asks it per row and
     * a comparison against a key is the cheapest true thing to ask.
     */
    public function changing(): string
    {
        return $this->changing ?? '';
    }

    /** Open a setting for changing, seeded with what it holds, where the screen offers a control for it. */
    public function change(string $key): void
    {
        $seed = $this->seedFor($key);

        if ($seed === null) {
            $this->never();

            return;
        }

        $this->startAgain();
        $this->changing = $key;
        $this->typed = $seed;
        $this->proposed = null;
    }

    /** Close the open setting without asking the stack anything. */
    public function never(): void
    {
        $this->startAgain();
        $this->changing = null;
        $this->typed = null;
        $this->proposed = null;
        $this->landingSaid = null;
    }

    /**
     * Ask what setting it to what has been typed would come to.
     *
     * Nothing is written. What comes back is shown, and the agreeing is a
     * second tap — which is what makes the thing an operator agrees to the
     * thing they were shown.
     */
    public function wouldBe(string $key): void
    {
        $named = $this->keptAs($key);

        if ($named !== null) {
            $typed = $this->typed;

            if ($typed !== null && $this->mayBePut($key, $named)) {
                $this->rehearseInTheStore(ASettingChange::toHold($named, $typed));
            }

            return;
        }

        $this->proposed = $this->put($key, static fn(
            Adjusting $adjusting,
            Stack $stack,
            Session $session,
            WhatToSet $asked,
        ): WhatTheStackMadeOfIt => $adjusting->wouldBe($stack, $session, $asked));
    }

    /**
     * Set it, having been shown what that would come to.
     *
     * The listing is dropped afterwards rather than patched: what the stack
     * now holds is the stack's to say, and a screen editing its own copy of
     * the row would be showing the operator what it believes rather than what
     * is there.
     */
    public function agree(string $key, string $letting = WhetherToWait::GoAheadNow->value): void
    {
        $named = $this->keptAs($key);

        if ($named !== null) {
            if ($this->mayBePut($key, $named) && $this->rehearsedOnly($named)) {
                $this->carryOutWhatWasRehearsed();
            }

            return;
        }

        if (WhetherToWait::tryFrom($letting) === WhetherToWait::ForTheDownloads) {
            $this->letThemLand($key);

            return;
        }

        $this->proposed = $this->put($key, static fn(
            Adjusting $adjusting,
            Stack $stack,
            Session $session,
            WhatToSet $asked,
        ): WhatTheStackMadeOfIt => $adjusting->agreedTo($stack, $session, $asked));
        $this->readAgainAfterAChange();
    }

    /** Whether a change agreed to once the downloads land is still waiting on them. */
    public function awaitsAnOutcome(): bool
    {
        return $this->landing !== null || $this->howItIsGoing()->stands->isWorking();
    }

    /**
     * Ask after a change agreed to once the downloads land, while the stack waits for them.
     *
     * What it came to replaces the proposal, as an agreed change's review does.
     */
    #[Poll(HowOftenAScreenLooks::WHILE_WORK_RUNS_MS)]
    public function whileTheyLand(): void
    {
        $this->going = $this->howItIsGoing()->stands->isWorking() ? null : $this->going;
        $landing = $this->landing;

        if ($landing === null) {
            return;
        }

        $stack = $this->stack();
        $standing = $this->proposed ?? new HowAChangeReads()->nothingOpen();
        $this->proposed = $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatAChangeTurnedOutToBe => $this->adjusting->whatBecameOf($stack, $session, Job::named($landing))->either(
                stillRunning: static fn(): WhatAChangeTurnedOutToBe => $standing,
                done: function (WhereTheChangeStands $stands): WhatAChangeTurnedOutToBe {
                    $came = new HowAChangeReads()->stands($stands);
                    $this->proposed = $came;
                    $this->landed();

                    return $came;
                },
                ended: function () use ($standing): WhatAChangeTurnedOutToBe {
                    $this->landed();
                    $this->landingSaid = 'config.landing.ended';

                    return $standing;
                },
                met: function (Obstacle $why) use ($stack): WhatAChangeTurnedOutToBe {
                    $this->landed();
                    $this->letGoOfTheSession($why, $stack);

                    return new HowAChangeReads()->met($why);
                },
                moved: function () use ($standing): WhatAChangeTurnedOutToBe {
                    $this->landed();
                    $this->landingSaid = 'config.landing.moved';

                    return $standing;
                },
            ),
            notHeld: function (): WhatAChangeTurnedOutToBe {
                $this->landed();

                return new HowAChangeReads()->signedOut();
            },
        );
    }

    /** Where the proposal stands, or nothing where none has been put. */
    public function proposal(): ?WhatAChangeTurnedOutToBe
    {
        return $this->proposed;
    }

    /**
     * The proposal, where it is about this row and came back.
     *
     * Asked per row rather than held once and compared in the template,
     * because a control inside a listing acts on the row it is drawn beside —
     * a screen holding one proposal and a template that forgot to check whose
     * it was would show the review of one setting under another.
     */
    public function proposalFor(string $key): ?WhatAChangeTurnedOutToBe
    {
        $proposed = $this->proposed;

        if (!$proposed instanceof WhatAChangeTurnedOutToBe || $proposed->key !== $key || ! $proposed->went->cameBack()) {
            return null;
        }

        return $proposed;
    }

    /** What the setting holds to open it with, or nothing where this screen offers no control for it. */
    abstract protected function seedFor(string $key): ?string;

    abstract protected function readAgainAfterAChange(): void;

    /** Whether this screen may put a change of this key to the stack, asked again at the moment it is put. */
    abstract protected function mayBeChanged(string $key): bool;

    /** The name `settings-set` takes for this key, as the stack's entry for it says, or nothing where `config-set` takes it. */
    abstract protected function keptAs(string $key): ?string;

    protected function theStoreAnswered(?TheSettingAsChanged $report): void
    {
        if (! $report instanceof TheSettingAsChanged || $report->did() !== WhatTheSettingRunDid::Rehearsed) {
            $this->readAgainAfterAChange();
        }
    }

    /**
     * Set it once the downloads land: the stack waits, and the screen follows the name it answers with.
     */
    private function letThemLand(string $key): void
    {
        $typed = $this->typed;

        if ($this->changing !== $key || $typed === null || ! $this->mayBeChanged($key)) {
            $this->proposed = new HowAChangeReads()->nothingOpen();

            return;
        }

        $stack = $this->stack();
        $asked = WhatToSet::to($key, $typed);
        $standing = $this->proposed ?? new HowAChangeReads()->nothingOpen();
        $this->landingSaid = null;

        $this->proposed = $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatAChangeTurnedOutToBe => $this->adjusting->agreedToOnceLanded($stack, $session, $asked)->either(
                started: function (Job $job) use ($standing): WhatAChangeTurnedOutToBe {
                    $this->landing = $job->shown();

                    return $standing;
                },
                met: function (Obstacle $why) use ($stack): WhatAChangeTurnedOutToBe {
                    $this->letGoOfTheSession($why, $stack);

                    return new HowAChangeReads()->met($why);
                },
            ),
            notHeld: static fn(): WhatAChangeTurnedOutToBe => new HowAChangeReads()->signedOut(),
        );
    }

    /** Whether this key is the one open, and both it and the name it is kept under may be changed, asked at the moment it is put. */
    private function mayBePut(string $key, string $named): bool
    {
        return $this->changing === $key && $this->mayBeChanged($key) && $this->mayBeChanged($named);
    }

    /** Whether the rehearsal a yes would carry out moves this setting and nothing else. */
    private function rehearsedOnly(string $named): bool
    {
        $moved = [];

        foreach ($this->asked ?? [] as $setting => $value) {
            $moved[$setting] = $value;
        }

        return array_keys($moved) === [$named];
    }

    /** The waiting is over: what the screen read is read again. */
    private function landed(): void
    {
        $this->landing = null;
        $this->readAgainAfterAChange();
    }

    /**
     * The one path to the stack that both asking and agreeing take.
     *
     * **The key is the caller's and is checked against the open one.** Every
     * control on this screen sits inside the listing, so each one names the
     * row it acts on — a tap that did not would be reaching for whichever
     * setting the screen happened to have open, which on a list is the wrong
     * row as often as the right one. Disagreeing with what is open means the
     * tap came from a row that is not the one being changed, and nothing is
     * put to the stack.
     *
     * @param Closure(Adjusting, Stack, Session, WhatToSet): WhatTheStackMadeOfIt $asking
     */
    private function put(string $key, Closure $asking): WhatAChangeTurnedOutToBe
    {
        $typed = $this->typed;

        if ($this->changing !== $key || $typed === null || ! $this->mayBeChanged($key)) {
            return new HowAChangeReads()->nothingOpen();
        }

        $stack = $this->stack();
        $asked = WhatToSet::to($key, $typed);

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatAChangeTurnedOutToBe => $asking(
                $this->adjusting,
                $stack,
                $session,
                $asked,
            )->either(
                said: static fn(WhereTheChangeStands $stands): WhatAChangeTurnedOutToBe
                    => new HowAChangeReads()->stands($stands),
                refused: function (Obstacle $why) use ($stack): WhatAChangeTurnedOutToBe {
                    $this->letGoOfTheSession($why, $stack);

                    return new HowAChangeReads()->met($why);
                },
            ),
            notHeld: static fn(): WhatAChangeTurnedOutToBe => new HowAChangeReads()->signedOut(),
        );
    }
}
