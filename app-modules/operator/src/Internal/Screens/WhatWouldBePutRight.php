<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Design\Api\WritesItsTaps;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\Check;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Confirmed;
use Modules\Kernel\Api\HearingTheJobs;
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
use Modules\Kernel\Api\WhyTheWorkEnded;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\FollowsAJob;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowAMendingReads;
use Modules\Operator\Internal\Presenters\HowAnOfferOfRepairsReads;
use Modules\Operator\Internal\Presenters\HowARefusalReads;
use Modules\Operator\Internal\RepairsFollowed;
use Modules\Operator\Internal\ViewModels\ARefusalAsShown;
use Modules\Operator\Internal\ViewModels\WhatTheStackWouldPutRight;
use Modules\Operator\Internal\ViewModels\WhatThisStackPutRight;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function trim;

use UnitEnum;

/**
 * What this stack would put right, stated before anybody is asked to agree.
 *
 * The order is the requirement as much as the content: what a
 * repair does, what else it affects and whether it can be undone are said
 * *before* confirmation is asked for. So this screen exists on its own rather
 * than as a dialog behind a button — a sentence an operator has to tap to
 * reveal is one they will agree without reading.
 *
 * **Asking and agreeing each answer a job**, followed in turn by
 * {@see FollowsAJob}: first the asking, which changes nothing, then the yes.
 * What comes back may well be *still working on it*, which is a state of this
 * screen, drawn with what the job says, rather than something to hide behind
 * a spinner that lies.
 *
 * **Asking again is a button, and which question it asks depends.** Where a job
 * is still running, it reads the same job — the work is the stack's and
 * repeating the read changes nothing. Where the job ended, it starts a new one,
 * because there is nothing left to read.
 *
 * `Concealed` for the reason every stack-facing screen here is: what a machine
 * would put right says a good deal about what is on it.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class WhatWouldBePutRight extends NativeComponent implements AwaitsAnOutcome
{
    use WritesItsTaps;
    use AsksTheStackAgain;
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    /** @use FollowsAJob<WhatTheStackWouldPutRight|WhatThisStackPutRight> */
    use FollowsAJob, FindsItsWayAround {
        FollowsAJob::letGoOfWhatElseItHears insteadof FindsItsWayAround;
    }
    use DrawsItsTemplate;

    public const string TEMPLATE = 'operator::what-would-be-put-right';

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
        protected readonly HearingTheJobs $hearingTheJobs,
    ) {}

    /** Whether this device still holds a session for it. */
    public function isSignedIn(): bool
    {
        if (! $this->offer()->stands->went->isSignedIn) {
            return false;
        }

        return ! $this->agreed || $this->done()->stands->went->isSignedIn;
    }

    /**
     * What this stack said it would put right, asked once per frame.
     *
     * One accessor rather than one per field, the same as {@see done()} beside
     * it: a template asking `$this->offer()->repairs` and
     * `$this->done()->outcomes` is asking two clearly different questions.
     */
    public function offer(): WhatTheStackWouldPutRight
    {
        $report = $this->whatBecameOfTheJob(RepairsFollowed::Asking);

        return $report instanceof WhatTheStackWouldPutRight ? $report : new HowAnOfferOfRepairsReads()->ended(null);
    }

    /** Whether the operator has agreed to something on this listing. */
    public function wasAgreedTo(): bool
    {
        return $this->agreed;
    }

    /** What became of what was agreed to, once anything was; nothing to report before. */
    public function done(): WhatThisStackPutRight
    {
        $report = $this->agreed ? $this->whatBecameOfTheJob(RepairsFollowed::TheYes) : null;

        return $report instanceof WhatThisStackPutRight ? $report : new HowAMendingReads()->ended(null);
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
     * and becomes a {@see Check} here after a blank is refused. Nothing this
     * screen listed is named nothing, so a blank is *no such repair* and comes
     * away the same as any other name it never read.
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
     * **The job is kept only while it is still going**, on either side. A job
     * that has finished answers the same thing however often it is read, and a
     * job that ended has nothing to read at all, so the asking is started
     * afresh. Only a run still in progress is worth returning to, because
     * reading it again is the only way to learn it has finished. A yes is read
     * again, whichever it is.
     */
    public function again(): void
    {
        if ($this->agreed || $this->awaitsAnOutcome()) {
            $this->askAfterAgain($this->agreed ? RepairsFollowed::TheYes : RepairsFollowed::Asking);

            return;
        }

        $this->forget(RepairsFollowed::Asking);
    }

    /** Whether the stack is carrying something out right now, as the frame reads it. */
    public function isWorking(): bool
    {
        return $this->agreed ? $this->done()->stands->isWorking() : $this->offer()->stands->isWorking();
    }

    /**
     * Look again at what this machine would put right.
     *
     * A listing usually holds more than one repair, and agreeing to one is not
     * agreeing to the rest. Everything is forgotten rather than the listing
     * being kept: the machine has just changed, so the repairs it would offer
     * now are not necessarily the ones it offered before.
     */
    public function lookAgain(): void
    {
        $this->forget(RepairsFollowed::Asking);
        $this->forget(RepairsFollowed::TheYes);
        $this->movedOn = null;
        $this->agreed = false;
        $this->yesTo = null;
        $this->offered = null;
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
        if (! $this->offer()->stands->went->wasHeldByOtherWork()) {
            return;
        }

        $yesTo = $this->yesTo;

        if ($yesTo === null) {
            $this->lookAgain();

            return;
        }

        $this->agreeTo($yesTo);
    }

    /** What became of the asking, or of the yes once there is one. */
    protected function whatBecameOfIt(Stack $stack, Session $session, Job $job, UnitEnum $for): WhatTheStackWouldPutRight|WhatThisStackPutRight
    {
        return match (RepairsFollowed::asked($for)) {
            RepairsFollowed::TheYes => $this->whatWasDone($stack, $session, $job),
            RepairsFollowed::Asking => $this->whatTheAskingCameTo($stack, $session, $job),
        };
    }

    /** Ask the stack what it would put right, and follow the job it answers with; before a yes, there is nothing it did. */
    protected function whileNothingIsFollowed(UnitEnum $for): WhatTheStackWouldPutRight|WhatThisStackPutRight
    {
        if (RepairsFollowed::asked($for) === RepairsFollowed::TheYes) {
            return new HowAMendingReads()->ended(null);
        }

        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatTheStackWouldPutRight => $this->mending->wouldPutRight($stack, $session)->either(
                started: function (Job $job) use ($stack, $session): WhatTheStackWouldPutRight {
                    $this->follow($job, RepairsFollowed::Asking);

                    return $this->whatTheAskingCameTo($stack, $session, $job);
                },
                met: $this->lettingGoIfRefused($stack, new HowAnOfferOfRepairsReads()->met(...)),
            ),
            notHeld: static fn(): WhatTheStackWouldPutRight => new HowAnOfferOfRepairsReads()->signedOut(),
        );
    }

    protected function withoutASession(UnitEnum $for): WhatTheStackWouldPutRight|WhatThisStackPutRight
    {
        return match (RepairsFollowed::asked($for)) {
            RepairsFollowed::TheYes => new HowAMendingReads()->ended(null),
            RepairsFollowed::Asking => new HowAnOfferOfRepairsReads()->signedOut(),
        };
    }

    /**
     * Send the yes, and follow the job it answers with.
     *
     * Split out because `H8` counts the doors {@see agreeTo()} would otherwise
     * have, and because the two are different questions: which repair, and what
     * to do about it.
     */
    private function sendTheYes(Offer $offer, Repair $repair): void
    {
        $stack = $this->stack();
        $against = $this->offer();

        $this->storage->resume($stack->id())->either(
            held: function (Session $session) use ($stack, $offer, $repair, $against): WhatTheStackWouldPutRight|WhatThisStackPutRight {
                $confirmed = Confirmed::against($repair, $offer, Reading::live($offer));

                return $this->mending->agreeTo($stack, $session, $confirmed)->either(
                    started: function (Job $job) use ($against): WhatTheStackWouldPutRight {
                        $this->follow($job, RepairsFollowed::TheYes);
                        $this->agreed = true;
                        $this->yesTo = null;
                        $this->movedOn = null;

                        return $against;
                    },
                    met: fn(Obstacle $why): WhatTheStackWouldPutRight|WhatThisStackPutRight
                        => $this->reported(new HowAnOfferOfRepairsReads()->met($why), RepairsFollowed::Asking),
                );
            },
            notHeld: fn(): WhatTheStackWouldPutRight|WhatThisStackPutRight
                => $this->reported(new HowAnOfferOfRepairsReads()->signedOut(), RepairsFollowed::Asking),
        );
    }

    /** What the stack did about the yes. */
    private function whatWasDone(Stack $stack, Session $session, Job $job): WhatThisStackPutRight
    {
        return $this->mending->whatWasDoneAbout($stack, $session, $job)->either(
            stillRunning: static fn(): WhatThisStackPutRight
                => new HowAMendingReads()->stillWorkingItOut(),
            done: static fn(WhatWasMended $mended): WhatThisStackPutRight
                => new HowAMendingReads()->these($mended),
            ended: static fn(?WhyTheWorkEnded $why): WhatThisStackPutRight => new HowAMendingReads()->ended($why),
            met: $this->lettingGoIfRefused($stack, new HowAMendingReads()->met(...)),
            moved: function (ARefusalInItsWords $why): WhatThisStackPutRight {
                $this->lookAgain();
                $this->movedOn = new HowARefusalReads()->inItsWords($why);

                return new HowAMendingReads()->ended(null);
            },
        );
    }

    /** What became of the asking, folded for the template. */
    private function whatTheAskingCameTo(Stack $stack, Session $session, Job $job): WhatTheStackWouldPutRight
    {
        return $this->mending->whatBecameOf($stack, $session, $job)->either(
            stillRunning: static fn(): WhatTheStackWouldPutRight
                => new HowAnOfferOfRepairsReads()->stillWorkingItOut(),
            offering: function (Offer $offer): WhatTheStackWouldPutRight {
                $this->offered = $offer;

                return new HowAnOfferOfRepairsReads()->offering($offer);
            },
            ended: static fn(?WhyTheWorkEnded $why): WhatTheStackWouldPutRight => new HowAnOfferOfRepairsReads()->ended($why),
            met: static fn(Obstacle $why): WhatTheStackWouldPutRight
                => new HowAnOfferOfRepairsReads()->met($why),
        );
    }
}
