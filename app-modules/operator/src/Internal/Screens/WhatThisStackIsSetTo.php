<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Closure;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\View\View;

use function is_string;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Design\Api\WritesItsTaps;
use Modules\Kernel\Api\ACommandToHand;
use Modules\Kernel\Api\Adjusting;
use Modules\Kernel\Api\Arranging;
use Modules\Kernel\Api\AskingHowItRunsWithoutLemonfiber;
use Modules\Kernel\Api\AskingWhatSetupSettled;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HowItRunsWithoutLemonfiber;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Settings;
use Modules\Kernel\Api\Sharing;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\WhatTheStackMadeOfIt;
use Modules\Kernel\Api\WhatToSet;
use Modules\Kernel\Api\WhereTheChangeStands;
use Modules\Kernel\Api\WhetherToWait;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowAChangeReads;
use Modules\Operator\Internal\Presenters\HowTheSettingsRead;
use Modules\Operator\Internal\ReadsHowItRunsWithout;
use Modules\Operator\Internal\ReadsWhatSetupSettled;
use Modules\Operator\Internal\ViewModels\WhatAChangeTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\WhatThisStackIsSetToTurnedOutToBe;
use Modules\Operator\Internal\WhatTheSheetSaid;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\Screens\ReadsAStackOnceAFrame;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * Everything this stack is set to, as the stack itself lists it.
 *
 * **The screen holds no list of settings and this is the point of it.** What
 * is drawn is the listing that came back, whatever was in it. An app that knew
 * the settings it could show would show a subset the day the stack gained one,
 * would show it silently, and would leave the operator to conclude the setting
 * does not exist.
 *
 * So there is no allow-list here, no ordering, no grouping and no labels of
 * this app's own: a key and what it holds, in the order the stack said them.
 * Anything prettier would be this side deciding something the other side owns.
 *
 * **What setup settled is drawn above the listing, read-only.** It is read
 * from the stack, and where setup is not finished the screen says to finish it
 * at the machine; setup is never offered here.
 *
 * **{@see Concealed} because a withheld row is still about a credential.** The
 * stack withholds the value and sends a note in its place, so nothing secret
 * reaches this screen — but the *names* do, and a task-switcher snapshot of a
 * list of credential keys is a list of what this household holds and where.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class WhatThisStackIsSetTo extends NativeComponent implements AwaitsAnOutcome
{
    use WritesItsTaps;
    use AsksTheStackAgain;
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;
    use ReadsAStackOnceAFrame;
    use ReadsHowItRunsWithout;
    use ReadsWhatSetupSettled;

    /** The key for the name the share sheet shows the words under. */
    private const string COPY_NAMED = 'without.copy_named';

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

    public ?WhatThisStackIsSetToTurnedOutToBe $answered = null;

    /** The catalogue key for what handing the words over came to, where they have been. */
    public ?string $copied = null;

    /** Which setting is open for changing, where one is. */
    public ?string $changing = null;

    /** Where the proposal stands, once one has been put to the stack. */
    public ?WhatAChangeTurnedOutToBe $proposed = null;

    /** The name of a change agreed to once the downloads land, while the stack waits for them. */
    public ?string $landing = null;

    /** The key for why a change agreed to once the downloads land was not made, where it was not. */
    public ?string $landingSaid = null;

    public function __construct(
        private readonly Arranging $arranging,
        private readonly Adjusting $adjusting,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
        protected readonly AskingHowItRunsWithoutLemonfiber $askingWithout,
        protected readonly AskingWhatSetupSettled $askingSettled,
        private readonly Sharing $sharing,
        private readonly Translator $catalogue,
    ) {}

    /** What the operator has typed into the setting open for changing, or nothing while none is. */
    public function typed(): ?string
    {
        return $this->typed;
    }


    /**
     * Ask again.
     *
     * Dropping the held answer rather than re-reading here, so the next thing
     * that wants it does the asking — one path to the stack instead of two
     * that can disagree about what happened.
     */
    public function again(): void
    {
        $this->answered = null;
        $this->proposed = null;
        $this->copied = null;
        $this->forgetHowItRunsWithout();
        $this->forgetWhatSetupSettled();
    }

    /**
     * Hand the words that bring the stack up without lemonfiber to the device's own sharing, to copy out.
     *
     * Only words the stack gave are offered; the sheet is the platform's and
     * where they go is the operator's choice.
     */
    public function copyTheWords(): void
    {
        $how = $this->heardWithout;

        if (! $how instanceof HowItRunsWithoutLemonfiber) {
            return;
        }

        $named = $this->catalogue->get(self::COPY_NAMED);

        $this->copied = $this->sharing->offerToCopy(ACommandToHand::of($how->plainUp(), is_string($named) ? $named : self::COPY_NAMED))->either(
            over: static fn(): WhatTheSheetSaid => new WhatTheSheetSaid('without.copied'),
            refused: static fn(): WhatTheSheetSaid => new WhatTheSheetSaid('without.not_copied'),
        )->said;
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

    /**
     * Open a setting for changing, seeded with what it holds.
     *
     * **Refuses a key the listing did not offer a control for.** The template
     * draws the control only on a shown row, so a withheld key can only
     * arrive here from something other than the screen — and a screen that
     * trusted its own template would be one whose guarantee lives in a blade
     * file. Nothing offers to set a credential's value, and this is the half
     * of that which does not depend on markup.
     */
    public function change(string $key): void
    {
        foreach ($this->answer()->set as $setting) {
            if ($setting->key === $key && $setting->mayBeChanged) {
                $this->changing = $key;
                $this->typed = $setting->said;
                $this->proposed = null;

                return;
            }
        }

        $this->never();
    }

    /** Close the open setting without asking the stack anything. */
    public function never(): void
    {
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
        $this->answered = null;
        $this->forgetWhatSetupSettled();
    }

    /** Whether a change agreed to once the downloads land is still waiting on them. */
    public function awaitsAnOutcome(): bool
    {
        return $this->landing !== null;
    }

    /**
     * Ask after a change agreed to once the downloads land, while the stack waits for them.
     *
     * What it came to replaces the proposal, as an agreed change's review does.
     */
    #[Poll(HowOftenAScreenLooks::WHILE_WORK_RUNS_MS)]
    public function whileTheyLand(): void
    {
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
                    $this->landed();

                    return new HowAChangeReads()->stands($stands);
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


    public function render(): View
    {
        $this->aFrameBegins();

        return view('operator::what-this-stack-is-set-to');
    }

    public function answer(): WhatThisStackIsSetToTurnedOutToBe
    {
        return $this->answered ??= $this->ask();
    }

    /**
     * Set it once the downloads land: the stack waits, and the screen follows the name it answers with.
     */
    private function letThemLand(string $key): void
    {
        $typed = $this->typed;

        if ($this->changing !== $key || $typed === null) {
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

    /** The waiting is over: the listing and what setup settled are read again. */
    private function landed(): void
    {
        $this->landing = null;
        $this->answered = null;
        $this->forgetWhatSetupSettled();
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

        if ($this->changing !== $key || $typed === null) {
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

    private function ask(): WhatThisStackIsSetToTurnedOutToBe
    {
        $this->readsItsStack();
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatThisStackIsSetToTurnedOutToBe => $this->asked($stack, $session),
            notHeld: static fn(): WhatThisStackIsSetToTurnedOutToBe => new HowTheSettingsRead()->signedOut(),
        );
    }

    private function asked(Stack $stack, Session $session): WhatThisStackIsSetToTurnedOutToBe
    {
        return $this->arranging->asItStands($stack, $session)->either(
            told: static fn(Settings $set): WhatThisStackIsSetToTurnedOutToBe
                => new HowTheSettingsRead()->these($set),
            refused: function (Obstacle $why) use ($stack): WhatThisStackIsSetToTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowTheSettingsRead()->met($why);
            },
        );
    }
}
