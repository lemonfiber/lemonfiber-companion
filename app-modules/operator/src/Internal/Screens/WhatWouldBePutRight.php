<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;

use function is_string;

use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Confirmed;
use Modules\Kernel\Api\HowOften;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Mending;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Offer;
use Modules\Kernel\Api\Reading;
use Modules\Kernel\Api\Repair;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\Stacks;
use Modules\Kernel\Api\WhatWasMended;
use Modules\Operator\Internal\WhatTheStackWouldPutRight;
use Modules\Operator\Internal\WhatThisStackPutRight;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * What this stack would put right, stated before anybody is asked to agree.
 *
 * `N2-R4` is the requirement and the order in it is the requirement: what a
 * repair does, what else it affects and whether it can be undone are said
 * *before* confirmation is asked for. So this screen exists on its own rather
 * than as a dialog behind a button — a sentence an operator has to tap to
 * reveal is one they will agree without reading.
 *
 * **Asking costs a round trip and answers a handle.** `N2-R7` has every action
 * on this surface arrive as a job, including the unconfirmed form that changes
 * nothing. The frame therefore asks and then reads, once each, and what comes
 * back may well be *still working on it* — which is a state of this screen
 * rather than something to hide behind a spinner that lies.
 *
 * **Asking again is a button, and which question it asks depends.** Where a job
 * is still running, it reads the same handle — the work is the stack's and
 * repeating the read changes nothing. Where the job ended, it starts a new one,
 * because there is nothing left to read. `N1-R17` keeps this from happening on
 * a timer: an operator on a home network with a machine that may be asleep
 * decides when to ask, and `N4-R4`'s argument about not re-asking for something
 * declined is the same argument one requirement over.
 *
 * **No yes here yet.** `N2-R5` has the agreeing be a separate act against a
 * named listing, and `Confirmed` is built and unreached. The listing's name is
 * carried on the fold for it, so that screen will not have to ask the stack
 * again for what this one is already showing.
 *
 * `Concealed` for the reason every stack-facing screen here is: what a machine
 * would put right says a good deal about what is on it.
 */
#[Lazy]
#[Concealed]
final class WhatWouldBePutRight extends NativeComponent
{
    /**
     * What came back, once the frame has asked.
     *
     * `protected` rather than private, which is what `NativeComponent`'s
     * property syncing needs to reach — it assigns from the parent class, so a
     * private member of a subclass becomes a dynamic property and the screen
     * silently stops holding what it thinks it holds.
     */
    protected ?WhatTheStackWouldPutRight $answered = null;

    /** What the stack did about the agreement, once it has been asked. */
    protected ?WhatThisStackPutRight $carriedOut = null;

    /**
     * The handle, while there is one.
     *
     * Held so that asking again can read the same job rather than starting a
     * second one — two handles for one question is two lots of work on
     * somebody's machine. Not shown and never persisted: `N1-R41` refuses an
     * action presented as pending, and a job name on the glass is exactly that.
     */
    protected ?string $handle = null;

    /**
     * The listing, while there is one to agree to.
     *
     * Held as the value rather than as the fold, because {@see Confirmed} can
     * only be made against the {@see Offer} itself — `N2-R6` has the yes quote
     * the listing it was given, and a flattened copy is not that listing.
     */
    protected ?Offer $offered = null;

    /**
     * Whether the operator has agreed to something.
     *
     * Which of the two readings a frame takes. Not a convenience: the answers
     * are different things, and a screen asking the wrong one would render a
     * listing of what a machine *would* do as a record of what it *did*.
     */
    protected bool $agreed = false;

    public function __construct(
        private readonly Mending $mending,
        private readonly SecureStorage $storage,
        private readonly Stacks $stacks,
    ) {}

    /**
     * The stack this screen is about.
     *
     * Read from the route on every frame rather than held, so there is one
     * answer to *which machine* and it is the one the URI names.
     */
    public function stack(): Stack
    {
        $named = $this->param('stack');

        return $this->stacks->configured()->stack(
            StackId::rememberedAs(is_string($named) ? $named : ''),
        );
    }

    /** Whether this device still holds a session for it (`N1-R44`). */
    public function isSignedIn(): bool
    {
        return $this->answer()->isSignedIn;
    }

    /**
     * What this stack said it would put right.
     *
     * One accessor rather than one per field, the same as {@see done()} beside
     * it — and the symmetry is worth having for its own sake: a template asking
     * `$this->offer()->repairs` and `$this->done()->outcomes` is asking two
     * clearly different questions, where six flat accessors and five more would
     * have read as one screen with eleven moods.
     *
     * It is also `Q-R64`'s twenty-method ceiling answered before it is met,
     * which is the lesson from `HowThisStackIs` arriving at twenty-one.
     */
    public function offer(): WhatTheStackWouldPutRight
    {
        return $this->answer();
    }

    /** Whether the operator has agreed to something on this listing. */
    public function wasAgreedTo(): bool
    {
        return $this->agreed;
    }

    /**
     * What became of what was agreed to, once anything was.
     *
     * One accessor rather than one per field, which is `Q-R64`'s twenty-method
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
     */
    public function agreeTo(string $check): void
    {
        $offer = $this->offered;

        if (! $offer instanceof Offer) {
            return;
        }

        foreach ($offer->repairs() as $repair) {
            if ($repair->answers() === $check) {
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
     * Look again while the stack is carrying the repair out (`N1-R27`).
     *
     * The one piece of content in this app that changes without anybody
     * touching the phone. `N1-R27` refuses a screen that relies on the operator
     * leaving and returning to see a change, and *ask again* as the only road
     * is exactly that with a button on it: somebody who told a machine to fix
     * something has to keep tapping to find out whether it did.
     *
     * **It does nothing unless the work is running**, which is what keeps this
     * from being the polling `N1-R17` refuses. A finished run answers the same
     * thing however often it is read and a screen showing an offer has nothing
     * to wait for, so the cadence costs a machine on a home network nothing in
     * either state.
     *
     * The interval is {@see HowOften}'s constant rather than a number written
     * here, because {@see cadenceSaid()} renders the same cadence into a
     * sentence and a screen that polled at one interval while stating another
     * would be stating a cadence it does not keep.
     */
    #[Poll(HowOften::WHILE_WORK_RUNS_MS)]
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
     * How often this screen looks again, as a key (`N1-R27`).
     *
     * The stated half of the requirement. A screen that refreshes silently is
     * one an operator cannot reason about: they do not know whether what they
     * are reading is a second old or a minute old, and whether something has
     * changed is the only reason they are looking.
     */
    public function cadenceSaid(): string
    {
        return HowOften::WhileWorkRuns->saidOnTheScreen();
    }

    /** How many seconds that is, for the sentence to count on. */
    public function cadenceSeconds(): int
    {
        return HowOften::WhileWorkRuns->seconds();
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
        $this->agreed = false;
        $this->handle = null;
        $this->offered = null;
        $this->answered = null;
        $this->carriedOut = null;
    }

    /**
     * Where this machine's screens are.
     *
     * One accessor rather than one per destination, and {@see WhereAStackIs}
     * is the only place that knows a stack's routes — six classes were each
     * spelling `/stacks/%s/sign-in` for themselves, so a rename had to be found
     * in all six and the one that was missed would be a button leading nowhere.
     */
    public function goes(): WhereAStackIs
    {
        return WhereAStackIs::of($this->stack()->id());
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
                        $this->carriedOut = null;

                        return WhatTheStackWouldPutRight::stillWorkingItOut();
                    },
                    met: fn(Obstacle $why): WhatTheStackWouldPutRight
                        => $this->answered = WhatTheStackWouldPutRight::met($why),
                );
            },
            notHeld: fn(): WhatTheStackWouldPutRight
                => $this->answered = WhatTheStackWouldPutRight::signedOut(),
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
            return WhatThisStackPutRight::ended();
        }

        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatThisStackPutRight
                => $this->mending->whatWasDoneAbout($stack, $session, Job::named($held))->either(
                    stillRunning: static fn(): WhatThisStackPutRight
                        => WhatThisStackPutRight::stillWorkingItOut(),
                    done: static fn(WhatWasMended $mended): WhatThisStackPutRight
                        => WhatThisStackPutRight::these($mended),
                    ended: static fn(): WhatThisStackPutRight => WhatThisStackPutRight::ended(),
                    met: static fn(Obstacle $why): WhatThisStackPutRight
                        => WhatThisStackPutRight::met($why),
                ),
            notHeld: static fn(): WhatThisStackPutRight => WhatThisStackPutRight::ended(),
        );
    }

    /** What came back, asked once per frame. */
    private function answer(): WhatTheStackWouldPutRight
    {
        return $this->answered ??= $this->ask();
    }

    /**
     * Resume the session, then ask the stack.
     *
     * Split from {@see answer()} because the two are different questions —
     * when to ask, and what asking produced — and because `H8` counts the doors
     * either would otherwise have.
     */
    private function ask(): WhatTheStackWouldPutRight
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatTheStackWouldPutRight => $this->read($stack, $session),
            notHeld: static fn(): WhatTheStackWouldPutRight => WhatTheStackWouldPutRight::signedOut(),
        );
    }

    /**
     * Read the handle, starting the work first where there is none.
     *
     * The two questions in the order `N2-R7` puts them, and the early return is
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
            met: static fn(Obstacle $why): WhatTheStackWouldPutRight => WhatTheStackWouldPutRight::met($why),
        );
    }

    /** What became of that handle, folded for the template. */
    private function became(Stack $stack, Session $session, Job $job): WhatTheStackWouldPutRight
    {
        return $this->mending->whatBecameOf($stack, $session, $job)->either(
            stillRunning: static fn(): WhatTheStackWouldPutRight
                => WhatTheStackWouldPutRight::stillWorkingItOut(),
            offering: function (Offer $offer): WhatTheStackWouldPutRight {
                // Kept as the value, not only as the fold: `Confirmed` can be
                // made against nothing else, which is `N2-R6` refusing a yes
                // that quotes a listing it was not given.
                $this->offered = $offer;

                return WhatTheStackWouldPutRight::offering($offer);
            },
            ended: static fn(): WhatTheStackWouldPutRight => WhatTheStackWouldPutRight::ended(),
            met: static fn(Obstacle $why): WhatTheStackWouldPutRight
                => WhatTheStackWouldPutRight::met($why),
        );
    }
}
