<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use function count;

use Illuminate\View\View;
use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Decided;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Requested;
use Modules\Kernel\Api\RequestId;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\Wanting;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Operator\Internal\AsText;
use Modules\Operator\Internal\LooksAgainWhileOpen;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowTheHouseholdsAskingReads;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\WhatOneRequestSays;
use Modules\Operator\Internal\ViewModels\WhatTheHouseholdTurnedOutToWant;
use Modules\Requests\Api\KeepingWhatWasAsked;
use Modules\Wayfinding\Api\Screens\AsksAgain;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\Element;
use Native\Mobile\Edge\NativeComponent;

use function trim;
use function view;

/**
 * What the people in the house have asked their stack for.
 *
 * Requests awaiting a decision have to be visible from a phone, and
 * until this screen existed {@see Wanted}, {@see \Modules\Kernel\Api\Size} and
 * {@see \Modules\Kernel\Api\Waiting} were written, tested and reached by
 * nothing — the household was the one part of a stack an operator could not see
 * at all. It is also the part they are asked about in person: somebody in the
 * house asked for something last Tuesday and wants to know what happened.
 *
 * **It asks once, when the frame is built, and holds what came back**, which is
 * {@see HowThisStackIs}'s shape and what is required: one read per frame,
 * and a home network with a machine that may be asleep is the wrong
 * thing to talk to four times a second. Every accessor below reads what one
 * asking produced.
 *
 * **It shows everything the house asked for, not only what is waiting.**
 * The requirement is about the decisions, and those are marked — but a screen that
 * listed only them would answer *what must I decide* and leave *what became of
 * the thing I asked for* unanswered, which is the question the household
 * actually asks its operator. The two are told apart on the row rather than by
 * hiding one of them.
 *
 * **It opens on what the phone kept.** The first frame draws the reading kept
 * from an earlier session, with how long ago it was read, before the stack is
 * asked anything; the fresh reading replaces it and is kept in its place.
 * Where the stack cannot be reached, the kept reading stays, beside what stood
 * in the way. Every approval and decline waits for a fresh reading.
 *
 * `Concealed` for the reason every stack-facing screen here is: what a house
 * watches is the household's business, and a diagnostic report is
 * assembled from what the operator chooses to send rather than from what a
 * screen happened to hold.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class WhatTheHouseholdAsked extends NativeComponent
{
    use AsksTheStackAgain;
    use LooksAgainWhileOpen;
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;
    use AsksAgain;

    /**
     * What came back, once the frame has asked.
     *
     * `public`, which is what `NativeComponent`'s property syncing needs to
     * reach: since 4.5.1 it writes only public, non-static properties, and a
     * screen whose state it cannot write silently stops holding what it thinks
     * it holds. It is also what fills the view's data, so the compiled
     * template finds the variable rather than an undefined one.
     */
    public ?WhatTheHouseholdTurnedOutToWant $answered = null;

    /** The request an operator is being asked to give a reason for, if any. */
    public ?WhatOneRequestSays $turningDown = null;

    /**
     * What they have typed as that reason.
     *
     * `public` for the reason above and one more: `native:model` syncs into it,
     * and the sync writes only public properties — so anything less would leave
     * the field looking filled while this held nothing.
     */
    public string $because = '';

    public function __construct(
        private readonly Wanting $wanting,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
        private readonly KeepingWhatWasAsked $kept,
    ) {}

    /**
     * Open the stack's stream and ask the stack, behind the first frame.
     *
     * The first frame drew what the phone kept; the next frame asks, and what
     * the stack says then replaces it.
     */
    public function mount(): void
    {
        $this->listen();
        $this->answered = null;
    }

    /** How many are shown, which is what the empty state asks. */
    public function howMany(): int
    {
        return count($this->answer()->requests);
    }

    /**
     * Approve one of them, which is half of seeing the requests and all of deciding.
     *
     * A pending request has to be approvable from lemonfiber without
     * opening Seerr. This is the method that makes it true on a phone: the
     * approval goes to the stack's own endpoint, and nothing here links out to
     * the tool the request came from. An operator asked about it in the kitchen
     * answers in the kitchen.
     *
     * The request is found in what was actually read before anything is sent,
     * for {@see WhatToDoWithThis::wouldYouLike()}'s reason: a number a template
     * passed in is a number this screen may never have shown, and a decision
     * about a request nobody was looking at is the whole harm this is
     * about. An approval owes the person who asked the thing they asked for and
     * nothing else, so there is no question in front of it.
     */
    public function approve(string $numbered): void
    {
        $request = $this->waitingOn($numbered);

        if (! $request instanceof WhatOneRequestSays) {
            return;
        }

        $this->send(Decided::toApprove(RequestId::numbered($request->number)));
    }

    /**
     * Start turning one down, which is a question rather than an act.
     *
     * The reason is part of declining, so this cannot send anything:
     * it holds the request while an operator writes the sentence the person who
     * asked is owed. {@see Decided::toDecline()} refuses a blank one, so no
     * road from here produces *declined* with nothing beside it.
     */
    public function wouldDecline(string $numbered): void
    {
        $this->because = '';
        $this->turningDown = $this->waitingOn($numbered);
    }

    /** Whether there is enough typed to turn it down with. */
    public function mayDecline(): bool
    {
        return trim($this->because) !== '';
    }

    /** The request being turned down, or nothing where none is. */
    public function turningDown(): ?WhatOneRequestSays
    {
        return $this->turningDown;
    }

    /**
     * Turn it down, with the sentence that was typed.
     *
     * It sends what was held rather than anything a template passes in, so the
     * request an operator read the reason against and the one the stack is told
     * about are the same request. Refused while the reading drawn is one the
     * phone kept: a request is decided only against a fresh reading.
     */
    public function decline(): void
    {
        $request = $this->turningDown;

        if ($this->answer()->waitsForTheStack || ! $request instanceof WhatOneRequestSays || ! $this->mayDecline()) {
            return;
        }

        $this->turningDown = null;
        $said = $this->because;
        $this->because = '';

        $this->send(Decided::toDecline(RequestId::numbered($request->number), $said));
    }

    /** Put the question away without deciding anything. */
    public function neverMind(): void
    {
        $this->turningDown = null;
        $this->because = '';
    }

    public function render(): View
    {
        // Handed the typed field, because `native:model` expands to a bare
        // variable and a screen that only held it would render a frame where
        // it was never defined — a warning rather than a stop, so the field
        // draws empty and the control beside it never enables.
        return view('operator::what-the-household-asked', ['because' => $this->because]);
    }

    /**
     * What came back, asked once per frame.
     *
     * One accessor handing out the value rather than one per field, which is
     * {@see WhatThisStackRuns::answer()}'s shape and its argument: a method per
     * field is a method this class spends on saying nothing, and the next fact
     * the template needs then costs one it does not have. The template reads
     * the fields off what one asking produced, which is also the only thing
     * that could be true of them together.
     */
    public function answer(): WhatTheHouseholdTurnedOutToWant
    {
        return $this->answered ??= $this->ask();
    }

    /**
     * The first frame: the reading the phone kept, drawn as the screen draws
     * any, with its age and every decision on it waiting.
     *
     * Held as the answer for this frame alone; {@see mount()} asks behind it.
     * Only where nothing was kept is the frame the platform's indicator.
     */
    protected function placeholder(): Element|View
    {
        return $this->kept->lastKept($this->stack()->id())->either(
            kept: function (Requested $wanted, Instant $readAt, Instant $now): View {
                $this->answered = new HowTheHouseholdsAskingReads()->kept($wanted, $readAt, $now, HowTheReadingWent::itCameBack());

                return $this->render();
            },
            nothing: fn(): Element|View => parent::placeholder(),
        );
    }

    /**
     * The row of that number in what was read, where it is waiting on a yes.
     *
     * Two refusals in one: a request this screen never showed, and one it
     * showed that is not waiting on anybody. The second matters as much as the
     * first — a request already declined is one an operator would be deciding
     * about twice, and the stack would be right to refuse the second.
     *
     * Nothing waits on a yes in a reading the phone kept: where a request stands
     * now is the fresh reading's to say, so every row of a kept one is refused.
     */
    private function waitingOn(string $numbered): ?WhatOneRequestSays
    {
        $answer = $this->answer();

        if ($answer->waitsForTheStack) {
            return null;
        }

        foreach ($answer->requests as $request) {
            if ((string) $request->number === trim($numbered) && $request->wantsADecision) {
                return $request;
            }
        }

        return null;
    }

    /**
     * Tell the stack, and forget what was read.
     *
     * The listing in front of the operator is about the household as it was
     * before they decided anything, so the next accessor asks again. What the
     * decision answered is not kept: what an operator wants to know is where
     * the request stands, which the next reading says.
     */
    private function send(Decided $decided): void
    {
        $stack = $this->stack();

        $this->storage->resume($stack->id())->either(
            held: fn(Session $session): AsText => $this->tell($stack, $session, $decided),
            notHeld: static fn(): AsText => AsText::nothing(),
        );

        $this->answered = null;
    }

    /**
     * Hand it to the port, and fold both arms to the same shape.
     *
     * Split out because `either()` wants two arms answering one type and a
     * closure that assigned a property in one of them would be doing the work
     * where the shape is being decided.
     */
    private function tell(Stack $stack, Session $session, Decided $decided): AsText
    {
        return $this->wanting->decided($stack, $session, $decided)->either(
            started: static fn(Job $job): AsText => AsText::of($job->shown()),
            met: function (Obstacle $why) use ($stack): AsText {
                // A credential refused the moment somebody taps approve is the
                // same signed-out device as one refused on a read, and this is
                // the call that happens on the tap.
                $this->letGoOfTheSession($why, $stack);

                return AsText::of($why->said());
            },
        );
    }

    /**
     * Resume the session, ask the stack, and flatten what came back.
     *
     * Split from {@see answer()} because the two are different questions — when
     * to ask, and what asking produced — and because `H8` counts the doors
     * either would otherwise have.
     */
    private function ask(): WhatTheHouseholdTurnedOutToWant
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatTheHouseholdTurnedOutToWant => $this->asked($stack, $session),
            notHeld: fn(): WhatTheHouseholdTurnedOutToWant => $this->besideWhatWasKept(new HowTheHouseholdsAskingReads()->signedOut()),
        );
    }

    /** What the stack said, or what the operator met instead. */
    private function asked(Stack $stack, Session $session): WhatTheHouseholdTurnedOutToWant
    {
        return $this->wanting->askedOf($stack, $session)->either(
            these: function (Requested $wanted) use ($stack): WhatTheHouseholdTurnedOutToWant {
                $this->kept->keep($stack->id(), $wanted);

                return new HowTheHouseholdsAskingReads()->these($wanted);
            },
            met: function (Obstacle $why) use ($stack): WhatTheHouseholdTurnedOutToWant {
                $this->letGoOfTheSession($why, $stack);

                return $this->besideWhatWasKept(new HowTheHouseholdsAskingReads()->met($why));
            },
        );
    }

    /**
     * A reading that did not come back, drawn beside the one the phone kept.
     *
     * No fresh reading arrived, so the kept one still stands, with its age and
     * every decision on it waiting; only where nothing was kept is what stood
     * in the way the whole screen.
     */
    private function besideWhatWasKept(WhatTheHouseholdTurnedOutToWant $unread): WhatTheHouseholdTurnedOutToWant
    {
        return $this->kept->lastKept($this->stack()->id())->either(
            kept: static fn(Requested $wanted, Instant $readAt, Instant $now): WhatTheHouseholdTurnedOutToWant
                => new HowTheHouseholdsAskingReads()->kept($wanted, $readAt, $now, $unread->askedNow),
            nothing: static fn(): WhatTheHouseholdTurnedOutToWant => $unread,
        );
    }
}
