<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;
use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\KeepingCurrent;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TakingAnUpdate;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\Upkeep;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\FollowsTheUpdateItTook;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowUpkeepReads;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\WhatTheUpkeepTurnedOutToBe;
use Modules\Updates\Api\KeepingTheLastUpkeep;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * Where this machine stands on being up to date.
 *
 * The up-to-date screen. It opens on the answer — up to date, or an update
 * waiting — because that is the decision an operator holding a phone is making.
 * They are not comparing version strings; they are deciding whether tonight is
 * the night.
 *
 * **It does not compare versions, and that is the requirement.** Whether any
 * service would move is the stack's answer, read off the pins and shown. The
 * release history is shown as history: it lists every release up to the one
 * running, and an update is the stack moving onto its own build's pins rather
 * than onto a release picked from that list.
 *
 * **A withdrawn release is said, not offered.** A stack running one is told so,
 * and moving onto the pins a withdrawn release carries is not offered.
 *
 * **It opens on what the phone kept, and acts only on a fresh reading.** The
 * first frame draws the reading kept from an earlier session, with how long
 * ago it was read, before the stack is asked anything; the fresh reading
 * replaces it and is kept in its place. Until one arrives — and where the
 * stack cannot be reached, beside what stood in the way — taking the update is
 * drawn and cannot be used, and refused here as well as on the glass.
 *
 * `Concealed` for the reason every stack-facing screen here is: what a house
 * runs is the household's business, and a diagnostic report is
 * assembled from what the operator chooses to send rather than from what a
 * screen happened to hold.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class HowCurrentThisStackIs extends NativeComponent implements AwaitsAnOutcome
{
    use AsksTheStackAgain;
    use OffersTheAppsSettings;
    use FollowsTheUpdateItTook;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;
    use DrawsItsTemplate;

    public const string TEMPLATE = 'operator::how-current-this-stack-is';

    /**
     * What came back, once the frame has asked.
     *
     * `public`, which is what `NativeComponent`'s property syncing needs to
     * reach: since 4.5.1 it writes only public, non-static properties, and a
     * screen whose state it cannot write silently stops holding what it thinks
     * it holds. It is also what fills the view's data, so the compiled
     * template finds the variable rather than an undefined one.
     */
    public ?WhatTheUpkeepTurnedOutToBe $answered = null;

    /**
     * The update being asked about, while the operator decides.
     *
     * `public` for the reason above, and held rather than re-read because what
     * is confirmed has to be the thing that was shown — services re-read after
     * the yes are an update to whatever the stack had by then, agreed against a
     * screen that is no longer true.
     */
    public ?TakingAnUpdate $asking = null;

    public function __construct(
        private readonly KeepingCurrent $keeping,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
        private readonly KeepingTheLastUpkeep $kept,
    ) {}

    /**
     * Open the stack's stream, behind the first frame.
     *
     * The first frame drew what the phone kept; the next asks the stack, and
     * what it says replaces it. Asked there rather than here, because a frame
     * is where a stack nothing is held for is asked what it serves first.
     */
    public function mount(): void
    {
        $this->listen();
        $this->answered = null;
    }

    /**
     * Ask the stack again.
     *
     * The action an obstacle must not take away. Forgetting what came back
     * rather than re-reading here, so the next accessor asks — which keeps this
     * one act and keeps the reading rule true: one asking per frame, and a frame that
     * starts when somebody taps.
     */
    public function again(): void
    {
        $this->answered = null;
        $this->lastUpdated = null;
    }

    /**
     * Look again at what is behind while nothing is being updated.
     *
     * Only the reading of what is behind: an update this screen took is
     * followed on its own cadence while it runs, and what it came to is held.
     */
    #[Poll(HowOftenAScreenLooks::WHILE_OPEN_MS)]
    public function whileOpen(): void
    {
        if (! $this->lastUpdate()->isWorking) {
            $this->answered = null;
        }
    }

    /**
     * Offer to take the update, and ask first.
     *
     * The offer comes from the reading, so where the stack offered none there
     * is nothing to ask about and this does nothing.
     *
     * Every update asks. There is no unconfirmed arm here the way there is for
     * a start, because there is no update that takes nothing away: it stops
     * services, and which ones is the whole of what the confirmation says.
     *
     * A reading the phone kept offers nothing yet: the control is drawn and
     * cannot be used, and a tap that reaches here anyway is refused.
     */
    public function wouldYouLike(): void
    {
        $answer = $this->answer();

        if ($answer->waitsForTheStack || ! $answer->offer instanceof TakingAnUpdate) {
            return;
        }

        $this->asking = $answer->offer;
    }

    /**
     * Take the update that was agreed to, and forget what was read.
     *
     * Forgetting rather than re-reading here, so the next accessor asks — the
     * listing after an update is taken is a different listing, and a screen
     * that kept the old one would show an evening that has already happened.
     *
     * Refused while the reading drawn is one the phone kept: an update is
     * agreed to only against a fresh one.
     */
    public function agree(): void
    {
        $taking = $this->asking;

        if ($this->answer()->waitsForTheStack || ! $taking instanceof TakingAnUpdate) {
            return;
        }

        $this->asking = null;

        $this->answered = null;
        $this->takeIt($taking);
    }

    /** Leave it. */
    public function neverMind(): void
    {
        $this->asking = null;
    }

    /**
     * What is being asked about, where anything is.
     *
     * The template draws the confirmation off this rather than off a flag, so
     * the thing named in the question is the thing that will be sent.
     */
    public function asking(): ?TakingAnUpdate
    {
        return $this->asking;
    }

    /** How many services the pending question would change. */
    public function wouldChange(): int
    {
        return $this->asking instanceof TakingAnUpdate ? $this->asking->changing()->count() : 0;
    }

    /** How many of those the stack said nothing will put back. */
    public function cannotBePutBack(): int
    {
        return $this->asking instanceof TakingAnUpdate ? $this->asking->cannotBePutBack()->count() : 0;
    }

    /**
     * What came back, asked once per frame.
     *
     * One accessor handing out the value rather than one per field: a method
     * per field is a method this class spends on saying nothing, and the next
     * fact the template needs then costs one it does not have.
     */
    public function answer(): WhatTheUpkeepTurnedOutToBe
    {
        return $this->answered ??= $this->ask();
    }

    /**
     * The first frame: the reading the phone kept, drawn as the screen draws
     * any, with its age and taking the update waiting.
     *
     * Held as the answer for this frame alone; {@see mount()} asks behind it.
     * Only where nothing was kept is the frame the platform's indicator.
     */
    protected function placeholder(): Element|View
    {
        return $this->kept->lastKept($this->stack()->id())->either(
            kept: function (Upkeep $upkeep, Instant $readAt): View {
                $this->answered = new HowUpkeepReads()->kept($upkeep, $readAt, $this->listening->clock->now(), HowTheReadingWent::itCameBack());

                return view('operator::how-current-this-stack-is');
            },
            nothing: fn(): Element|View => parent::placeholder(),
        );
    }

    /**
     * Resume the session, ask the stack, and flatten what came back.
     *
     * Split from {@see answer()} because the two are different questions — when
     * to ask, and what asking produced — and because `H8` counts the doors
     * either would otherwise have.
     */
    private function ask(): WhatTheUpkeepTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatTheUpkeepTurnedOutToBe => $this->asked($stack, $session),
            notHeld: fn(): WhatTheUpkeepTurnedOutToBe => $this->besideWhatWasKept(new HowUpkeepReads()->signedOut()),
        );
    }

    /** What the stack said, or what the operator met instead. */
    private function asked(Stack $stack, Session $session): WhatTheUpkeepTurnedOutToBe
    {
        return $this->keeping->standing($stack, $session)->either(
            stands: function (Upkeep $upkeep) use ($stack): WhatTheUpkeepTurnedOutToBe {
                $this->kept->keep($stack->id(), $upkeep, $this->listening->clock->now());

                return new HowUpkeepReads()->standing($upkeep);
            },
            met: function (Obstacle $why) use ($stack): WhatTheUpkeepTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return $this->besideWhatWasKept(new HowUpkeepReads()->met($why));
            },
        );
    }

    /**
     * A reading that did not come back, drawn beside the one the phone kept.
     *
     * No fresh reading arrived, so the kept one still stands, with its age and
     * every action on it waiting; only where nothing was kept is what stood in
     * the way the whole screen.
     */
    private function besideWhatWasKept(WhatTheUpkeepTurnedOutToBe $unread): WhatTheUpkeepTurnedOutToBe
    {
        $now = $this->listening->clock->now();

        return $this->kept->lastKept($this->stack()->id())->either(
            kept: static fn(Upkeep $upkeep, Instant $readAt): WhatTheUpkeepTurnedOutToBe => new HowUpkeepReads()->kept($upkeep, $readAt, $now, $unread->askedNow),
            nothing: static fn(): WhatTheUpkeepTurnedOutToBe => $unread,
        );
    }
}
