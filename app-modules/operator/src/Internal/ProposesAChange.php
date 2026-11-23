<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use function array_keys;

use Closure;
use Modules\Kernel\Api\Adjusting;
use Modules\Kernel\Api\ASettingChange;
use Modules\Kernel\Api\HowAgreedWorkIsGoing;
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
use Modules\Kernel\Api\WhyTheWorkEnded;
use Modules\Operator\Internal\Presenters\HowAChangeReads;
use Modules\Operator\Internal\Presenters\HowALandingReads;
use Modules\Operator\Internal\Presenters\HowASettingChangeReads;
use Modules\Operator\Internal\ViewModels\HowTheLandingWent;
use Modules\Operator\Internal\ViewModels\SaysWhereTheWorkStands;
use Modules\Operator\Internal\ViewModels\TheSettingChangeTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\WhatAChangeTurnedOutToBe;
use UnitEnum;

/**
 * Changing one setting through the stack's review: asked, agreed to, and followed while the downloads land.
 *
 * It follows two jobs through {@see FollowsAJob}, each with its own report:
 * a change to the settings store, and a change agreed to once the downloads
 * land, which may wait a long while. Another setting may be opened and
 * changed meanwhile; the landing is drawn on the row it changes, and on no
 * other.
 *
 * The write is the one the stack names for the key: `settings-set` under the
 * name the screen's `keptAs()` reads off the stack's entry, `config-set` where
 * it names none.
 */
trait ProposesAChange
{
    use PutsASettingChange;
    /** @use FollowsAJob<SaysWhereTheWorkStands> */
    use FollowsAJob;

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

    /** The setting a change agreed to once the downloads land is for, while one is followed. */
    public string $landingOn = '';

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

    /** The change agreed to once the downloads land, as the row it changes draws it, asked once per frame. */
    public function landing(): HowTheLandingWent
    {
        $report = $this->whatBecameOfTheJob(ASettingChangeFollowed::OnceLanded);

        return $report instanceof HowTheLandingWent ? $report : new HowALandingReads()->nothingWaits();
    }

    /**
     * The proposal, where it is about this row and came back: what a change
     * agreed to once the downloads land came to, once it was made, and the
     * review open for this row otherwise.
     *
     * Asked per row rather than held once and compared in the template,
     * because a control inside a listing acts on the row it is drawn beside —
     * a screen holding one proposal and a template that forgot to check whose
     * it was would show the review of one setting under another.
     */
    public function proposalFor(string $key): ?WhatAChangeTurnedOutToBe
    {
        $landed = $this->landing();
        $proposed = $landed->key === $key && $landed->came instanceof WhatAChangeTurnedOutToBe ? $landed->came : $this->proposed;

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

    /** What became of the change to the settings store, or of the change waiting for the downloads. */
    protected function whatBecameOfIt(Stack $stack, Session $session, Job $job, UnitEnum $for): TheSettingChangeTurnedOutToBe|HowTheLandingWent
    {
        return match (ASettingChangeFollowed::asked($for)) {
            ASettingChangeFollowed::InTheStore => $this->whatBecameOfTheChange($stack, $session, $job),
            ASettingChangeFollowed::OnceLanded => $this->whatTheLandingCameTo($this->adjusting->whatBecameOf($stack, $session, $job), $stack),
        };
    }

    protected function whileNothingIsFollowed(UnitEnum $for): TheSettingChangeTurnedOutToBe|HowTheLandingWent
    {
        return match (ASettingChangeFollowed::asked($for)) {
            ASettingChangeFollowed::InTheStore => new HowASettingChangeReads()->notAsked(),
            ASettingChangeFollowed::OnceLanded => new HowALandingReads()->nothingWaits(),
        };
    }

    protected function withoutASession(UnitEnum $for): TheSettingChangeTurnedOutToBe|HowTheLandingWent
    {
        return match (ASettingChangeFollowed::asked($for)) {
            ASettingChangeFollowed::InTheStore => new HowASettingChangeReads()->signedOut(),
            ASettingChangeFollowed::OnceLanded => new HowALandingReads()->signedOut(),
        };
    }

    protected function theStoreAnswered(?TheSettingAsChanged $report): void
    {
        if (! $report instanceof TheSettingAsChanged || $report->did() !== WhatTheSettingRunDid::Rehearsed) {
            $this->readAgainAfterAChange();
        }
    }

    /**
     * Set it once the downloads land: the stack waits, and the screen follows the job it answers with.
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

        $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatAChangeTurnedOutToBe => $this->adjusting->agreedToOnceLanded($stack, $session, $asked)->either(
                started: function (Job $job) use ($key): WhatAChangeTurnedOutToBe {
                    $this->follow($job, ASettingChangeFollowed::OnceLanded);
                    $this->landingOn = $key;
                    $this->reported(new HowALandingReads()->waiting($key), ASettingChangeFollowed::OnceLanded);

                    return $this->proposed ?? new HowAChangeReads()->nothingOpen();
                },
                met: function (Obstacle $why) use ($stack): WhatAChangeTurnedOutToBe {
                    $this->letGoOfTheSession($why, $stack);

                    return $this->proposed = new HowAChangeReads()->met($why);
                },
            ),
            notHeld: fn(): WhatAChangeTurnedOutToBe => $this->proposed = new HowAChangeReads()->signedOut(),
        );
    }

    /** What the waiting came to, on the row it changes; once it is over, what the screen read is read again. */
    private function whatTheLandingCameTo(HowAgreedWorkIsGoing $landed, Stack $stack): HowTheLandingWent
    {
        $key = $this->landingOn;
        $reads = new HowALandingReads();
        $came = $landed->either(
            stillRunning: static fn(): HowTheLandingWent => $reads->waiting($key),
            done: static fn(WhereTheChangeStands $stands): HowTheLandingWent => $reads->made($key, $stands),
            ended: static fn(?WhyTheWorkEnded $why): HowTheLandingWent => $reads->ended($key, $why),
            met: function (Obstacle $why) use ($stack, $key, $reads): HowTheLandingWent {
                $this->letGoOfTheSession($why, $stack);

                return $reads->met($key, $why);
            },
            moved: static fn(): HowTheLandingWent => $reads->moved($key),
        );

        if (! $came->stands->isWorking()) {
            $this->readAgainAfterAChange();
        }

        return $came;
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
