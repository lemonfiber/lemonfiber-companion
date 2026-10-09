<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;

use function is_string;

use Modules\Kernel\Api\AgreedTo;
use Modules\Kernel\Api\AnOffer;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Disturbances;
use Modules\Kernel\Api\Form;
use Modules\Kernel\Api\HearingTheStart;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Rehearsing;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Supervising;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\WhatStartingItWouldComeTo;
use Modules\Kernel\Api\WhatToDoWithIt;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\FollowsWhatTheVerbCameTo;
use Modules\Operator\Internal\HearsWhatAStartWaitsOn;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowARehearsalReads;
use Modules\Operator\Internal\Presenters\HowAVerbReads;
use Modules\Operator\Internal\Presenters\HowOneThingReads;
use Modules\Operator\Internal\ShowsWhatTheYesWillRun;
use Modules\Operator\Internal\TakesItsFormsAFrameLater;
use Modules\Operator\Internal\ViewModels\WhatAVerbTakesAwaySays;
use Modules\Operator\Internal\ViewModels\WhatOneServiceSays;
use Modules\Operator\Internal\ViewModels\WhatOneThingIs;
use Modules\Operator\Internal\ViewModels\WhatStartingItWouldShow;
use Modules\Operator\Internal\ViewModels\WhatThisStackRunsTurnedOutToBe;
use Modules\Services\Api\KeepingWhatItRuns;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

use function trim;
use function view;

/**
 * One thing this machine runs, and what may be done with it.
 *
 * The verbs are here rather than on {@see WhatThisStackRuns} because a list is
 * read and a verb is chosen, and the two acts do not want the same frame. Drawn
 * once per row, four services put fifteen controls on one screen and four of
 * them answer to *Start it*: which service a control acts on is then carried by
 * where it sits, and position is the one thing somebody being read to cannot
 * check.
 *
 * **A service and a form arrive here together, and that is not a shortcut.**
 * Both granularities are asked for, and what an operator is choosing between
 * is identical either way; what the stack is told differs only in which name it
 * carries. A second screen would be this one with a word changed, and two
 * screens offering one decision is how they come to offer it differently.
 *
 * **The yes is built from the listing, never from the route.** A
 * disruptive action to state what it disturbs before it is confirmed, and the
 * way that requirement is broken is never deliberate: a handler passes its
 * argument straight to the port. So {@see wouldYouLike()} takes the verb alone,
 * finds what the URI names in what was actually read, and builds
 * {@see AgreedTo} from *that* — a name this screen never read cannot be acted
 * on, however it arrives.
 *
 * **A service wins over a form where both could match.** That is a stack which
 * named a service after its form, and the narrower reading is the safer one:
 * agreeing about one service and being sent a whole form is the mistake that
 * costs a household something.
 *
 * **A start is not confirmed, and a stop, a restart and a fetch are.** That
 * line is {@see WhatToDoWithIt::asksFirst()}'s and is not redrawn here. A fetch
 * takes nothing away and is asked about for its cost in time and the line. A
 * screen that asked about a start would be teaching an operator to confirm
 * without reading, which is what makes the stop confirmation worth anything.
 *
 * **A verb is followed to what it came to.** Its handle is asked after until
 * the stack reports, and the report is drawn: what those services amount to,
 * every service a start or a restart did not bring back, a start the stack
 * declined with its reason, a rehearsal as one, what was left out and which
 * ports something else holds. A restart that brought back four services of
 * five is said as not having brought everything back, and the one is named.
 *
 * **It polls only while a verb runs or something is settling.** A service
 * that is starting becomes a running one on its own, and *ask again* as the
 * only road to finding out is the reliance on leaving and returning that rule
 * refuses.
 *
 * `Concealed` for the reason every stack-facing screen here is: what a house
 * runs is the household's business, and a diagnostic report is
 * assembled from what the operator chooses to send rather than from what a
 * screen happened to hold.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class WhatToDoWithThis extends NativeComponent implements AwaitsAnOutcome
{
    use AsksTheStackAgain;
    use OffersTheAppsSettings;
    use TakesItsFormsAFrameLater;
    use FollowsWhatTheVerbCameTo;
    use ShowsWhatTheYesWillRun;
    use HearsWhatAStartWaitsOn {
        HearsWhatAStartWaitsOn::letGoOfWhatElseItHears insteadof FindsItsWayAround;
    }
    use FindsItsWayAround;

    /** What the operator has been asked about, where a verb is waiting on a yes. */
    public ?AgreedTo $asking = null;

    /** What starting this form would come to, once the frame has asked. Public for {@see HowCurrentThisStackIs::$answered}'s reason. */
    public ?WhatStartingItWouldShow $rehearsed = null;

    /**
     * Whether what is running was last read before the verb being followed ended.
     *
     * `public` so the screen holds it between frames.
     */
    public bool $listingTrailsTheVerb = false;

    public function __construct(
        private readonly Supervising $supervising,
        private readonly Rehearsing $rehearsing,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly HearingTheStart $hearingTheStart,
        protected readonly WhatItListensWith $listening,
        private readonly KeepingWhatItRuns $kept,
    ) {}

    /**
     * What came back, asked once per frame.
     *
     * One accessor handing out the whole fold rather than one per field, which
     * is what keeps this screen under `H3`'s twenty methods. The asking itself
     * is {@see AsksWhatTheStackIsRunning}'s, and is handed what it needs.
     */
    public function answer(): WhatThisStackRunsTurnedOutToBe
    {
        return $this->listingOf($this->stack(), $this->storage, $this->supervising, $this->kept);
    }

    /**
     * What the route names, and what may be done with it.
     *
     * One accessor handing out the value it folded, which is `H3`'s own advice
     * for a screen: an accessor per field is what takes a class past twenty,
     * and the four answers here are one answer.
     */
    public function thing(): WhatOneThingIs
    {
        $said = $this->param('service');
        $named = is_string($said) ? trim($said) : '';
        $reads = new HowOneThingReads();

        // The forms are asked only where the name is not a service's, and on
        // the frame after the listing.
        return $reads->service($this->answer(), $named)
            ?? $reads->form($named, $this->formsOf($this->stack(), $this->storage, $this->supervising, $this->kept));
    }

    /**
     * What starting this form would come to, as the stack rehearses it.
     *
     * Asked only where the template draws a form, on a frame that has not
     * read the stack already, and nothing until then. Shown before the verbs,
     * so what a start would bring up and leave out is on the screen before
     * anybody starts it; nothing is started by asking. Not asked while the
     * listing drawn is one the phone kept, which a fresh listing ends.
     */
    public function rehearsal(): ?WhatStartingItWouldShow
    {
        if ($this->rehearsed instanceof WhatStartingItWouldShow || $this->answer()->waitsForTheStack || ! $this->mayReadItsStack()) {
            return $this->rehearsed;
        }

        return $this->rehearsed = $this->rehearse(Form::called($this->thing()->named));
    }

    /**
     * Ask about a verb, or carry it out where it asks nothing first.
     *
     * Where the verb asks first it is held rather than carried out, and
     * {@see agree()} is the only thing that sends it. That is the rule in the
     * shape of a method: this one cannot act on a verb that asks first however
     * it is called.
     *
     * Refused while the listing drawn is one the phone kept: a verb is sent
     * only against a fresh listing.
     */
    public function wouldYouLike(string $doing): void
    {
        $verb = WhatToDoWithIt::tryFrom($doing);

        if ($this->answer()->waitsForTheStack || ! $verb instanceof WhatToDoWithIt) {
            return;
        }

        $agreed = $this->agreementFor($verb);

        if (! $agreed instanceof AgreedTo) {
            return;
        }

        if ($agreed->doing()->asksFirst()) {
            $this->asking = $agreed;
            $this->rehearseTheQuestion($agreed);

            return;
        }

        $this->send($agreed);
    }

    /**
     * Carry out what the operator has just agreed to.
     *
     * It sends what was held and nothing a template passed in, so the thing
     * that was confirmed and the thing that happens are the same value.
     * Refused while the listing drawn is one the phone kept.
     */
    public function agree(): void
    {
        $agreed = $this->asking;

        if ($this->answer()->waitsForTheStack || ! $agreed instanceof AgreedTo) {
            return;
        }

        $this->asking = null;
        $this->movedOn = null;
        $this->forgetTheRehearsal();

        $this->send($agreed);
    }

    /** Put the question away without doing anything about it. */
    public function neverMind(): void
    {
        $this->asking = null;
        $this->movedOn = null;
        $this->forgetTheRehearsal();
    }

    /**
     * How long the pending question would take its subject away for.
     *
     * Read off the same listing the question was built from, so the number an
     * operator confirms on is the one the stack reported on the reading they
     * are looking at — not one fetched when they tapped, and not one this app
     * worked out. The second is forbidden, and the first would be a
     * different stack's answer by the time it arrived. A kept listing is
     * nothing to confirm on, so while the screen draws one there is none.
     */
    public function whatItTakesAway(): ?WhatAVerbTakesAwaySays
    {
        $agreed = $this->asking;
        $answer = $this->answer();
        $disturbs = $answer->disturbs;

        if (! $agreed instanceof AgreedTo || $answer->waitsForTheStack || ! $disturbs instanceof Disturbances) {
            return null;
        }

        return new HowAVerbReads()->against($disturbs, $agreed->doing());
    }

    /**
     * What the operator is being asked about, or nothing where they are not.
     *
     * The value itself rather than a flag beside it, so the template renders
     * the sentence from what will actually be sent — a screen that stated one
     * service and held another is exactly the failure this is about.
     */
    public function asking(): ?AgreedTo
    {
        return $this->asking;
    }

    /**
     * Whether the question is a restart of a service already being started over and over.
     *
     * Only a restart: another restart of a service in that state joins a queue
     * of starts, and a stop or a start is not a restart, so the warning is
     * not true of either.
     */
    public function aRestartWouldNotHelp(): bool
    {
        $agreed = $this->asking;
        $service = $this->thing()->service;

        if (! $agreed instanceof AgreedTo || ! $service instanceof WhatOneServiceSays) {
            return false;
        }

        return $agreed->doing() === WhatToDoWithIt::Restart && $service->wouldNotHelp;
    }

    /**
     * Look again while the machine is settling into what it was told.
     *
     * It does nothing unless a verb sent here is still running or something
     * is actually settling, which is what keeps this from being the polling
     * that is refused: a finished report and a stack whose services are all
     * in standing states answer the same thing however often they are read.
     *
     * It decides from what it last heard and reads nothing itself, so the
     * frame it leads to reads the stack once: while a verb runs, the frame
     * asks after the verb alone, and once the verb has ended, the next frame
     * reads what is running again.
     */
    #[Poll(HowOftenAScreenLooks::WHILE_WORK_RUNS_MS)]
    public function whileItSettles(): void
    {
        $this->listenWhileItStarts();
        $this->followTheRehearsal();

        if ($this->awaitsAnOutcome()) {
            $this->cameTo = null;
            $this->listingTrailsTheVerb = true;

            return;
        }

        if ($this->listingTrailsTheVerb || $this->answered?->isSettling === true) {
            $this->listingTrailsTheVerb = false;
            $this->answered = null;
        }
    }

    /**
     * Ask the stack again: what it runs, and what the verb sent here came to.
     *
     * Both, because a verb that finished changed what the listing says, and a
     * report that could not be asked after is one the operator can ask for
     * again rather than one they leave the screen to retry.
     */
    public function again(): void
    {
        $this->answered = null;
        $this->cameTo = null;
        $this->formsAgain();
    }

    public function render(): View
    {
        $this->aFrameBegins();

        return view('operator::what-to-do-with-this');
    }

    /** The question on the screen takes the name the stack gave what its rehearsal offers, which the yes carries back. */
    protected function rehearsalOffered(AnOffer $offer): void
    {
        if ($this->asking instanceof AgreedTo) {
            $this->asking = $this->asking->quoting($offer);
        }
    }

    /**
     * A yes the stack refused because what it was given for has moved: the
     * verb is asked about again, under nothing it was offered before, and
     * rehearsed afresh, so what it would do now is what is agreed to next.
     */
    protected function offerAgain(AgreedTo $sent): void
    {
        $this->sent = null;
        $this->asking = $sent->quoting(AnOffer::none());
        $this->rehearseTheQuestion($this->asking);
    }

    /**
     * The agreement a verb amounts to, against what was read.
     *
     * A service wins over a form where both could match, for the reason the
     * class docblock gives.
     */
    private function agreementFor(WhatToDoWithIt $doing): ?AgreedTo
    {
        $thing = $this->thing();

        if ($thing->service instanceof WhatOneServiceSays) {
            return $doing->reachesAService() ? AgreedTo::theService($doing, ServiceId::called($thing->named)) : null;
        }

        return $thing->isAForm ? AgreedTo::theForm($doing, Form::called($thing->named)) : null;
    }

    /**
     * Send it, and forget what was read.
     *
     * The listing in front of the operator is about the machine as it was
     * before they said anything, so the next accessor asks again. What the
     * verb came to is {@see FollowsWhatTheVerbCameTo}'s to follow.
     */
    private function send(AgreedTo $agreed): void
    {
        $this->waitsOn = '';
        $this->tellIt($agreed);
        $this->answered = null;
    }

    /** Ask the stack to rehearse starting that form, or say what stood in the way. */
    private function rehearse(Form $form): WhatStartingItWouldShow
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatStartingItWouldShow => $this->rehearsing->whatStarting($stack, $session, $form)->either(
                found: static fn(WhatStartingItWouldComeTo $rehearsal): WhatStartingItWouldShow => new HowARehearsalReads()->of($rehearsal),
                met: $this->lettingGoIfRefused($stack, new HowARehearsalReads()->met(...)),
            ),
            notHeld: static fn(): WhatStartingItWouldShow => new HowARehearsalReads()->signedOut(),
        );
    }
}
