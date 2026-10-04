<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;

use function is_string;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Confirmed;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Mending;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Offer;
use Modules\Kernel\Api\Reading;
use Modules\Kernel\Api\Repair;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\WhatWasMended;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowAMendingReads;
use Modules\Operator\Internal\Presenters\HowAnOfferOfRepairsReads;
use Modules\Operator\Internal\Presenters\HowARefusalReads;
use Modules\Operator\Internal\ViewModels\ARefusalAsShown;
use Modules\Operator\Internal\ViewModels\WhatTheStackWouldPutRight;
use Modules\Operator\Internal\ViewModels\WhatThisStackPutRight;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

use function trim;
use function view;

/**
 * What this stack would put right, stated before anybody is asked to agree.
 *
 * The order is the requirement as much as the content: what a
 * repair does, what else it affects and whether it can be undone are said
 * *before* confirmation is asked for. So this screen exists on its own rather
 * than as a dialog behind a button — a sentence an operator has to tap to
 * reveal is one they will agree without reading.
 *
 * **Asking costs a round trip and answers a handle.** Every action
 * on this surface arrive as a job, including the unconfirmed form that changes
 * nothing. The frame therefore asks and then reads, once each, and what comes
 * back may well be *still working on it* — which is a state of this screen
 * rather than something to hide behind a spinner that lies.
 *
 * **Asking again is a button, and which question it asks depends.** Where a job
 * is still running, it reads the same handle — the work is the stack's and
 * repeating the read changes nothing. Where the job ended, it starts a new one,
 * because there is nothing left to read. The cadence rule keeps this from happening on
 * a timer: an operator on a home network with a machine that may be asleep
 * decides when to ask, and the argument about not re-asking for something
 * declined is the same argument one requirement over.
 *
 * **No yes here yet.** Agreeing is a separate act against a
 * named listing, and `Confirmed` is built and unreached. The listing's name is
 * carried on the fold for it, so that screen will not have to ask the stack
 * again for what this one is already showing.
 *
 * `Concealed` for the reason every stack-facing screen here is: what a machine
 * would put right says a good deal about what is on it.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class WhatWouldBePutRight extends NativeComponent implements AwaitsAnOutcome
{
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;

    /**
     * What came back, once the frame has asked.
     *
     * `public`, which is what `NativeComponent`'s property syncing needs to
     * reach: since 4.5.1 it writes only public, non-static properties, and a
     * screen whose state it cannot write silently stops holding what it thinks
     * it holds. It is also what fills the view's data, so the compiled
     * template finds the variable rather than an undefined one.
     */
    public ?WhatTheStackWouldPutRight $answered = null;

    /** What the stack did about the agreement, once it has been asked. */
    public ?WhatThisStackPutRight $carriedOut = null;

    /**
     * The handle, while there is one.
     *
     * Held so that asking again can read the same job rather than starting a
     * second one — two handles for one question is two lots of work on
     * somebody's machine. Not shown and never persisted: an
     * action presented as pending, and a job name on the glass is exactly that.
     */
    public ?string $handle = null;

    /**
     * The listing, while there is one to agree to.
     *
     * Held as the value rather than as the fold, because {@see Confirmed} can
     * only be made against the {@see Offer} itself — a yes quotes
     * the listing it was given, and a flattened copy is not that listing.
     */
    public ?Offer $offered = null;

    /**
     * Whether the operator has agreed to something.
     *
     * Which of the two readings a frame takes. Not a convenience: the answers
     * are different things, and a screen asking the wrong one would render a
     * listing of what a machine *would* do as a record of what it *did*.
     */
    public bool $agreed = false;

    /**
     * The check of the repair last agreed to, while that yes has not been taken.
     *
     * So a yes other work held can be sent again for the same repair, against
     * the listing still held. Cleared once the stack takes it, and by looking
     * again, which forgets the listing it was given for.
     */
    public ?string $yesTo = null;

    /**
     * Why the last yes was refused because its offer moved, in the stack's words.
     *
     * Drawn above the offer as it stands now, so the operator reads what moved
     * before agreeing again. Cleared by a fresh yes and by looking again.
     */
    public ?ARefusalAsShown $movedOn = null;

    public function __construct(
        private readonly Mending $mending,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
    ) {}


    /** Whether this device still holds a session for it. */
    public function isSignedIn(): bool
    {
        if (! $this->offer()->went->isSignedIn) {
            return false;
        }

        // The outcome read can meet a refused credential after the
        // offer read succeeded, and one frame of a screen showing what it
        // loaded a moment ago under a session the stack has stopped
        // recognising is exactly what the requirement forbids. Both folds are
        // asked, so whichever one met it is the one that answers.
        return ! $this->agreed || $this->done()->went->isSignedIn;
    }

    /**
     * What this stack said it would put right, asked once per frame.
     *
     * One accessor rather than one per field, the same as {@see done()} beside
     * it — and the symmetry is worth having for its own sake: a template asking
     * `$this->offer()->repairs` and `$this->done()->outcomes` is asking two
     * clearly different questions, where six flat accessors and five more would
     * have read as one screen with eleven moods.
     *
     * It is also the twenty-method ceiling answered before it is met,
     * which is the lesson from `HowThisStackIs` arriving at twenty-one.
     */
    public function offer(): WhatTheStackWouldPutRight
    {
        return $this->answered ??= $this->ask();
    }

    /** Whether the operator has agreed to something on this listing. */
    public function wasAgreedTo(): bool
    {
        return $this->agreed;
    }

    /**
     * What became of what was agreed to, once anything was.
     *
     * One accessor rather than one per field, which is the twenty-method
     * ceiling answered before it is met — and reads better anyway: a template
     * asking `$this->done()->outcomes` is asking one question.
     */
    public function done(): WhatThisStackPutRight
    {
        return $this->carriedOut ??= $this->askWhatWasDone();
    }

    /**
     * Agree to one of the repairs on offer.
     *
     * Takes the check rather than a position, because a position is a fact
     * about a list this screen re-reads every frame and the check is a fact
     * about the repair. A listing that came back in another order between the
     * render and the tap would agree to a different repair, and the operator
     * would have no way of knowing.
     *
     * Silent where there is no listing held, which is a frame that has not read
     * one yet or one whose job ended — there is nothing to agree to, and a
     * refusal would be a sentence about a button the template does not draw.
     *
     * The name arrives as text because a template can hand over nothing else,
     * and becomes a {@see Check} here after a blank is refused —
     * {@see WhatThisStackRuns::row()}'s argument about a service name, and the
     * same one. Nothing this screen listed is named nothing, so a blank is *no
     * such repair* and comes away the same as any other name it never read.
     */
    public function agreeTo(string $named): void
    {
        $offer = $this->offered;

        if (! $offer instanceof Offer || trim($named) === '') {
            return;
        }

        $check = Check::of($named);

        foreach ($offer->repairs() as $repair) {
            if ($repair->answers()->is($check)) {
                $this->yesTo = $named;
                $this->sendTheYes($offer, $repair);

                return;
            }
        }
    }

    /**
     * Ask again, because the operator said so.
     *
     * **The handle is kept only while the work is still going**, on either
     * side. A job that has finished answers the same thing however often it is
     * read, so keeping its handle would make this re-render what is already on
     * the screen — and the operator tapping it has just changed something and
     * wants to know whether it took. A job that ended has nothing to read at
     * all. Only a run still in progress is worth returning to, because reading
     * it again is the only way to learn it has finished.
     */
    public function again(): void
    {
        if ($this->agreed) {
            $this->carriedOut = null;

            return;
        }

        if ($this->answered?->isWorking !== true) {
            $this->handle = null;
        }

        $this->answered = null;
    }

    /**
     * Look again while the stack is carrying the repair out.
     *
     * The one piece of content in this app that changes without anybody
     * touching the phone. A screen may not rely on the operator
     * leaving and returning to see a change, and *ask again* as the only road
     * is exactly that with a button on it: somebody who told a machine to fix
     * something has to keep tapping to find out whether it did.
     *
     * **It does nothing unless the work is running**, which is what keeps this
     * from being the polling that is refused. A finished run answers the same
     * thing however often it is read and a screen showing an offer has nothing
     * to wait for, so the cadence costs a machine on a home network nothing in
     * either state.
     *
     * The interval is {@see HowOftenAScreenLooks}'s constant rather than a number written
     * here, so every screen that waits on work waits on the same one.
     */
    #[Poll(HowOftenAScreenLooks::WHILE_WORK_RUNS_MS)]
    public function whileItRuns(): void
    {
        if (! $this->isWorking()) {
            return;
        }

        $this->again();
    }

    /**
     * Whether the stack is carrying something out right now.
     *
     * Published because the template branches on it and the cadence above
     * reads it, and those two must agree: a screen that said *this is running*
     * while the poll had stopped would leave somebody watching a sentence that
     * will never change.
     */
    public function isWorking(): bool
    {
        return $this->agreed ? $this->done()->isWorking : $this->offer()->isWorking;
    }

    /**
     * Look again at what this machine would put right.
     *
     * A listing usually holds more than one repair, and agreeing to one is not
     * agreeing to the rest. Without this the operator who fixed the disk is
     * left looking at that one outcome with no way back to the credential still
     * waiting beside it — a screen that can be entered once per listing, which
     * is not what a listing is.
     *
     * Everything is forgotten rather than the listing being kept, and that is
     * deliberate: the machine has just changed, so the repairs it would offer
     * now are not necessarily the ones it offered before. Keeping the old
     * listing would have somebody agree to a fix for something that has already
     * been put right.
     */
    public function lookAgain(): void
    {
        $this->movedOn = null;
        $this->agreed = false;
        $this->yesTo = null;
        $this->handle = null;
        $this->offered = null;
        $this->answered = null;
        $this->carriedOut = null;
    }

    /**
     * Send the request other work held again: the yes, or asking what it would put right.
     *
     * Only where other work held the stack, for
     * {@see \Modules\Operator\Internal\FollowsWhatTheVerbCameTo::tryAgain()}'s
     * reason. A yes is sent for the same repair against the listing still
     * held; with no yes outstanding, the request held was the question.
     */
    public function tryAgain(): void
    {
        if ($this->answered?->went->wasHeldByOtherWork() !== true) {
            return;
        }

        $yesTo = $this->yesTo;

        if ($yesTo === null) {
            $this->lookAgain();

            return;
        }

        $this->agreeTo($yesTo);
    }


    /** The same question this screen's cadence asks, answered from what it last heard. */
    public function awaitsAnOutcome(): bool
    {
        return $this->isWorking();
    }

    public function render(): View
    {
        return view('operator::what-would-be-put-right');
    }

    /**
     * Send the yes, and hold the handle it answers with.
     *
     * Split out because `H8` counts the doors {@see agreeTo()} would otherwise
     * have, and because the two are different questions: which repair, and what
     * to do about it.
     */
    private function sendTheYes(Offer $offer, Repair $repair): void
    {
        $stack = $this->stack();

        $this->storage->resume($stack->id())->either(
            held: function (Session $session) use ($stack, $offer, $repair): WhatTheStackWouldPutRight {
                $confirmed = Confirmed::against($repair, $offer, Reading::live($offer));

                return $this->mending->agreeTo($stack, $session, $confirmed)->either(
                    started: function (Job $job): WhatTheStackWouldPutRight {
                        $this->handle = $job->shown();
                        $this->agreed = true;
                        $this->yesTo = null;
                        $this->carriedOut = null;
                        $this->movedOn = null;

                        return new HowAnOfferOfRepairsReads()->stillWorkingItOut();
                    },
                    met: fn(Obstacle $why): WhatTheStackWouldPutRight
                        => $this->answered = new HowAnOfferOfRepairsReads()->met($why),
                );
            },
            notHeld: fn(): WhatTheStackWouldPutRight
                => $this->answered = new HowAnOfferOfRepairsReads()->signedOut(),
        );
    }

    /** What the stack did about it, asked once per frame. */
    private function askWhatWasDone(): WhatThisStackPutRight
    {
        $held = $this->handle;

        // No handle is *nothing to report*, which is what a frame that has not
        // been agreed to on says — and it is the same answer as a job the stack
        // has forgotten, because both mean there is no run to describe. Asked
        // before agreeing rather than guarded against: a screen that answered
        // "still working it out" for a run nobody started would be inventing
        // one, and the template's own branch on {@see wasAgreedTo()} is what
        // keeps it off the glass.
        if (! is_string($held)) {
            return new HowAMendingReads()->ended();
        }

        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatThisStackPutRight
                => $this->mending->whatWasDoneAbout($stack, $session, Job::named($held))->either(
                    stillRunning: static fn(): WhatThisStackPutRight
                        => new HowAMendingReads()->stillWorkingItOut(),
                    done: static fn(WhatWasMended $mended): WhatThisStackPutRight
                        => new HowAMendingReads()->these($mended),
                    ended: static fn(): WhatThisStackPutRight => new HowAMendingReads()->ended(),
                    met: function (Obstacle $why) use ($stack): WhatThisStackPutRight {
                        $this->letGoOfTheSession($why, $stack);

                        return new HowAMendingReads()->met($why);
                    },
                    // Refused and re-offered: the yes was given for an offer
                    // that has moved, so everything held for it is let go and
                    // the offer is asked for again, under what the stack said.
                    moved: function (ARefusalInItsWords $why): WhatThisStackPutRight {
                        $this->lookAgain();
                        $this->movedOn = new HowARefusalReads()->inItsWords($why);

                        return new HowAMendingReads()->ended();
                    },
                ),
            notHeld: static fn(): WhatThisStackPutRight => new HowAMendingReads()->ended(),
        );
    }

    /**
     * Resume the session, then ask the stack.
     *
     * Split from {@see Offer()} because the two are different questions —
     * when to ask, and what asking produced — and because `H8` counts the doors
     * either would otherwise have.
     */
    private function ask(): WhatTheStackWouldPutRight
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatTheStackWouldPutRight => $this->read($stack, $session),
            notHeld: static fn(): WhatTheStackWouldPutRight => new HowAnOfferOfRepairsReads()->signedOut(),
        );
    }

    /**
     * Read the handle, starting the work first where there is none.
     *
     * The two questions in the order they are asked in, and the early return is
     * what makes *ask again* a second read rather than a second piece of work
     * on somebody's machine: a handle already in hand is read, and only the
     * absence of one starts anything.
     *
     * The name is kept inside the `started` arm rather than after the call,
     * because that is the only place it exists — the other arm has no handle to
     * keep, which is exactly what an `either()` is for.
     */
    private function read(Stack $stack, Session $session): WhatTheStackWouldPutRight
    {
        $held = $this->handle;

        if (is_string($held)) {
            return $this->became($stack, $session, Job::named($held));
        }

        return $this->mending->wouldPutRight($stack, $session)->either(
            started: function (Job $job) use ($stack, $session): WhatTheStackWouldPutRight {
                $this->handle = $job->shown();

                return $this->became($stack, $session, $job);
            },
            met: function (Obstacle $why) use ($stack): WhatTheStackWouldPutRight {
                $this->letGoOfTheSession($why, $stack);

                return new HowAnOfferOfRepairsReads()->met($why);
            },
        );
    }

    /** What became of that handle, folded for the template. */
    private function became(Stack $stack, Session $session, Job $job): WhatTheStackWouldPutRight
    {
        return $this->mending->whatBecameOf($stack, $session, $job)->either(
            stillRunning: static fn(): WhatTheStackWouldPutRight
                => new HowAnOfferOfRepairsReads()->stillWorkingItOut(),
            offering: function (Offer $offer): WhatTheStackWouldPutRight {
                // Kept as the value, not only as the fold: `Confirmed` can be
                // made against nothing else, which is a yes refused when it
                // that quotes a listing it was not given.
                $this->offered = $offer;

                return new HowAnOfferOfRepairsReads()->offering($offer);
            },
            ended: static fn(): WhatTheStackWouldPutRight => new HowAnOfferOfRepairsReads()->ended(),
            met: static fn(Obstacle $why): WhatTheStackWouldPutRight
                => new HowAnOfferOfRepairsReads()->met($why),
        );
    }
}
