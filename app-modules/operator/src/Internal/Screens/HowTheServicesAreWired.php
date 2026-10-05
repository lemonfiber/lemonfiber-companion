<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use function array_any;

use Closure;
use Illuminate\View\View;

use function in_array;

use Modules\Kernel\Api\AFill;
use Modules\Kernel\Api\AFillAgreed;
use Modules\Kernel\Api\AFillTurnedDown;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Capability;
use Modules\Kernel\Api\ChoosingAFiller;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Linking;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Supervising;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\TheLinks;
use Modules\Kernel\Api\TheWiring;
use Modules\Kernel\Api\WhatBecameOfTheFill;
use Modules\Kernel\Api\WhatBecameOfTheWiring;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\WhyTheFillWasTurnedDown;
use Modules\Kernel\Api\WiringTheServices;
use Modules\Operator\Internal\AsksWhatTheStackIsRunning;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowTheChoiceReads;
use Modules\Operator\Internal\Presenters\HowTheLinksRead;
use Modules\Operator\Internal\Presenters\HowTheWiringReads;
use Modules\Operator\Internal\ReadsAStackOnceAFrame;
use Modules\Operator\Internal\ViewModels\ALinkAsShown;
use Modules\Operator\Internal\ViewModels\TheChoiceTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\TheLinksTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\TheWiringTurnedOutToBe;
use Modules\Operator\Internal\ViewModels\WhatThisStackRunsTurnedOutToBe;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * Wiring a stack's services to each other, and how each connection turned out.
 *
 * **What answers what comes first.** Every capability a service asks for,
 * which service answers it and how that was settled, read fresh each time the
 * screen opens and each time it is asked again, and kept nowhere on the phone.
 * A contest is drawn as one, with every claimant and nothing picked.
 *
 * **Choosing who answers is two steps with the operator between them.**
 * Where two or more services claim a capability, each one that does not
 * answer it already is offered. Tapping one asks the stack what the choice
 * would come to and writes nothing; the screen draws what answers it now,
 * what would answer it after, what asks for it and what the choice would
 * leave unfilled, and takes an optional reason. Only the yes makes it, and
 * the yes names the reading that was drawn, so a reading that moved in
 * between is worked out again and shown rather than made.
 *
 * **A run is offered as the act it is.** It changes nothing already right and
 * keeps what the operator changed, so there is no question before it. What
 * the screen opens on is the services the stack runs, which are what a run
 * wires to each other, read once per frame the way {@see TakingACopyHere}
 * reads them; nothing is wired until the run is tapped.
 *
 * **Every connection is drawn in the state the stack gave it**, with the
 * stack's words, and a run that only said what it would do says so first.
 * Nothing here offers to put lemonfiber's value back over the operator's.
 *
 * **The run is work the stack names and this follows**, so the handle is held
 * and asked after at {@see HowOftenAScreenLooks::WhileWorkRuns} while it runs, the way
 * {@see AskingSomebodyIn} follows an invitation.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class HowTheServicesAreWired extends NativeComponent implements AwaitsAnOutcome
{
    use OffersTheAppsSettings;
    use AsksWhatTheStackIsRunning;
    use FindsItsWayAround;
    use ReadsAStackOnceAFrame;

    /** The handle of the run being followed, while there is one. */
    public ?string $following = null;

    /** Where the run has got to, once this frame has asked. Public for {@see WhatIsRunningHere::$answered}'s reason. */
    public ?TheWiringTurnedOutToBe $going = null;

    /** What answers what, once this frame has asked. Public for the same reason. */
    public ?TheLinksTurnedOutToBe $linked = null;

    /**
     * The choice of filler the stack worked out, while it is in front of the operator.
     *
     * Held as the value rather than as the fold, because a yes can only be
     * agreed against the reading itself.
     */
    public ?AFill $shown = null;

    /** What choosing came to, as it is drawn. Public for {@see self::$going}'s reason. */
    public ?TheChoiceTurnedOutToBe $choice = null;

    /**
     * The reason being typed for the choice.
     *
     * `public` because `native:model` syncs into it, and the sync writes only
     * public properties.
     */
    public string $because = '';

    public function __construct(
        private readonly WiringTheServices $wiring,
        private readonly Linking $linking,
        private readonly ChoosingAFiller $fillers,
        private readonly Supervising $supervising,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
    ) {}


    /** The services the stack runs, which a run wires to each other; asked once per frame. */
    public function answer(): WhatThisStackRunsTurnedOutToBe
    {
        if (! $this->answered instanceof WhatThisStackRunsTurnedOutToBe) {
            $this->readsItsStack();
            $this->answered = $this->askWhatIsRunning($this->stack(), $this->storage, $this->supervising);
        }

        return $this->answered;
    }

    /**
     * What the stack wires to what, or nothing while it waits for a frame of its own.
     *
     * Asked only on a frame that has not read the stack already, so it follows
     * the services by a frame.
     */
    public function whatAnswersWhat(): ?TheLinksTurnedOutToBe
    {
        if ($this->linked instanceof TheLinksTurnedOutToBe || ! $this->mayReadItsStack()) {
            return $this->linked;
        }

        $stack = $this->stack();

        return $this->linked = $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheLinksTurnedOutToBe => $this->linking->linkedOn($stack, $session)->either(
                links: static fn(TheLinks $links): TheLinksTurnedOutToBe => new HowTheLinksRead()->these($links),
                refused: static fn(ARefusalInItsWords $why): TheLinksTurnedOutToBe => new HowTheLinksRead()->refused($why),
                met: function (Obstacle $why) use ($stack): TheLinksTurnedOutToBe {
                    $this->letGoOfTheSession($why, $stack);

                    return new HowTheLinksRead()->met($why);
                },
            ),
            notHeld: static fn(): TheLinksTurnedOutToBe => new HowTheLinksRead()->signedOut(),
        );
    }

    /**
     * Ask what choosing this service to answer this capability would come to.
     *
     * Writes nothing. The pair is found in what was read before anything is
     * sent, so a name a template passed in that this screen never offered
     * asks nothing.
     */
    public function choose(string $capability, string $service): void
    {
        if (! $this->offers($capability, $service)) {
            return;
        }

        $this->letGo();
        $this->choice = $this->choosing(
            fn(Stack $stack, Session $session): WhatBecameOfTheFill => $this->fillers->whatItWouldComeTo($stack, $session, Capability::called($capability), ServiceId::called($service)),
        );
    }

    /**
     * Make the choice drawn, with the reason typed.
     *
     * Silent where no reading is held: there is nothing to agree to. A
     * reading that moved since it was drawn is worked out again and drawn in
     * its place, waiting on a yes of its own.
     */
    public function agree(): void
    {
        $shown = $this->shown;

        if (! $shown instanceof AFill) {
            return;
        }

        $agreed = AFillAgreed::after($shown, $this->because);
        $this->choice = $this->choosing(
            fn(Stack $stack, Session $session): WhatBecameOfTheFill => $this->fillers->choose($stack, $session, $agreed),
        );
    }

    /** Put the choice away without making it, or once what became of it has been read. */
    public function letGo(): void
    {
        $this->shown = null;
        $this->choice = null;
        $this->because = '';
    }

    /** Start a wiring run. */
    public function wire(): void
    {
        $this->following = null;
        $this->going = $this->put(
            fn(Stack $stack, Session $session): WhatBecameOfTheWiring => $this->wiring->wire($stack, $session),
        );
    }

    /**
     * Ask again, which an obstacle must not take away.
     *
     * Asks for the services again, and after the run being followed where
     * there is one. An answer the stack gave about a run — its report, its
     * refusal, or its having no outcome — stays on the screen, because there
     * is nothing further to ask about it. An obstacle met starting a run is
     * let go of, and the request that never reached the stack is not sent
     * again on its own.
     */
    public function again(): void
    {
        $this->answered = null;
        $this->linked = null;
        $going = $this->going;

        if ($this->following !== null || ! $going instanceof TheWiringTurnedOutToBe || ! $going->went->cameBack()) {
            $this->going = null;
        }
    }

    /**
     * Ask after the run again while the stack is carrying it out.
     *
     * Nothing happens unless it is running, so a finished answer is not asked
     * for again. The interval is {@see HowOftenAScreenLooks}'s.
     */
    #[Poll(HowOftenAScreenLooks::WHILE_WORK_RUNS_MS)]
    public function whileItRuns(): void
    {
        if ($this->howItIsGoing()->isWorking) {
            $this->going = null;
        }
    }


    /** The same question this screen's cadence asks, answered from what it last heard. */
    public function awaitsAnOutcome(): bool
    {
        return $this->howItIsGoing()->isWorking;
    }

    public function render(): View
    {
        $this->aFrameBegins();

        // Handed the typed reason, because `native:model` expands to a bare
        // variable, and a frame where it was never defined draws the field
        // empty.
        return view('operator::how-the-services-are-wired', ['because' => $this->because]);
    }

    /**
     * Where the run has got to, asked once per frame.
     *
     * Asks the stack after the run being followed where there is one, and
     * otherwise says nothing has been asked.
     */
    public function howItIsGoing(): TheWiringTurnedOutToBe
    {
        $following = $this->following;

        return $this->going ??= $following === null
            ? new HowTheWiringReads()->notAsked()
            : $this->put(
                fn(Stack $stack, Session $session): WhatBecameOfTheWiring => $this->wiring->whatBecameOf($stack, $session, Job::named($following)),
            );
    }

    /**
     * One request put to the stack, with the session this device holds for it.
     *
     * @param Closure(Stack, Session): WhatBecameOfTheWiring $asking
     */
    private function put(Closure $asking): TheWiringTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheWiringTurnedOutToBe => $this->shown($asking($stack, $session), $stack),
            notHeld: static fn(): TheWiringTurnedOutToBe => new HowTheWiringReads()->signedOut(),
        );
    }

    /** What the stack said, as the screen draws it, holding what to follow. */
    private function shown(WhatBecameOfTheWiring $became, Stack $stack): TheWiringTurnedOutToBe
    {
        return $became->either(
            underway: function (Job $job): TheWiringTurnedOutToBe {
                $this->following = $job->shown();

                return new HowTheWiringReads()->running();
            },
            answered: function (TheWiring $wiring): TheWiringTurnedOutToBe {
                $this->following = null;

                return new HowTheWiringReads()->answered($wiring);
            },
            ended: function (): TheWiringTurnedOutToBe {
                $this->following = null;

                return new HowTheWiringReads()->ended();
            },
            refused: function (string $because): TheWiringTurnedOutToBe {
                $this->following = null;

                return new HowTheWiringReads()->refused($because);
            },
            met: function (Obstacle $why) use ($stack): TheWiringTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowTheWiringReads()->met($why);
            },
        );
    }

    /** Whether the screen offered this service as a choice for this capability, in what it last read. */
    private function offers(string $capability, string $service): bool
    {
        $linked = $this->linked;

        if (! $linked instanceof TheLinksTurnedOutToBe) {
            return false;
        }

        return array_any($linked->links, fn(ALinkAsShown $link): bool => $link->capability === $capability && in_array($service, $link->choices, strict: true));
    }

    /**
     * One question about a choice put to the stack, with the session this device holds for it.
     *
     * @param Closure(Stack, Session): WhatBecameOfTheFill $asking
     */
    private function choosing(Closure $asking): TheChoiceTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TheChoiceTurnedOutToBe => $this->chosen($asking($stack, $session), $stack, $session),
            notHeld: static fn(): TheChoiceTurnedOutToBe => new HowTheChoiceReads()->signedOut(),
        );
    }

    /**
     * What the stack said about a choice, as the screen draws it, holding the reading a yes is given against.
     *
     * A choice made leaves what answers what to be read again, so the rows
     * draw it. A reading that moved is worked out again; a reason that cannot
     * be kept leaves the same reading to be agreed to with another.
     */
    private function chosen(WhatBecameOfTheFill $became, Stack $stack, Session $session): TheChoiceTurnedOutToBe
    {
        $shown = $this->shown;
        $this->shown = null;

        return $became->either(
            fill: function (AFill $fill): TheChoiceTurnedOutToBe {
                if ($fill->wasMade()) {
                    $this->linked = null;
                    $this->because = '';

                    return new HowTheChoiceReads()->made($fill);
                }

                $this->shown = $fill;

                return new HowTheChoiceReads()->read($fill);
            },
            turnedDown: fn(AFillTurnedDown $down): TheChoiceTurnedOutToBe => $this->turnedDown($down, $shown, $stack, $session),
            refused: static fn(ARefusalInItsWords $why): TheChoiceTurnedOutToBe => new HowTheChoiceReads()->refused($why),
            met: function (Obstacle $why) use ($stack): TheChoiceTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowTheChoiceReads()->met($why);
            },
        );
    }

    /**
     * A choice the stack turned down, and what is still in front of the operator after it.
     *
     * Only two refusals leave something to agree to: a reading that moved is
     * worked out again, and a reason that cannot be kept leaves the reading
     * as it was. Every other one leaves nothing.
     */
    private function turnedDown(AFillTurnedDown $down, ?AFill $shown, Stack $stack, Session $session): TheChoiceTurnedOutToBe
    {
        if (! $shown instanceof AFill || ! in_array($down->why(), [WhyTheFillWasTurnedDown::Moved, WhyTheFillWasTurnedDown::ReasonCannotBeKept], strict: true)) {
            return new HowTheChoiceReads()->turnedDown($down);
        }

        if ($down->why() === WhyTheFillWasTurnedDown::Moved) {
            return $this->readAgain($shown, $down, $stack, $session);
        }

        $this->shown = $shown;

        return new HowTheChoiceReads()->turnedDown($down, $shown);
    }

    /** The choice that moved, worked out again from the wiring as it stands, with what the stack said moved. */
    private function readAgain(AFill $moved, AFillTurnedDown $down, Stack $stack, Session $session): TheChoiceTurnedOutToBe
    {
        $again = $this->chosen($this->fillers->whatItWouldComeTo($stack, $session, $moved->capability(), $moved->now()), $stack, $session);
        $fresh = $this->shown;

        return $fresh instanceof AFill ? new HowTheChoiceReads()->turnedDown($down, $fresh) : $again;
    }
}
