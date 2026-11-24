<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\ARefusalInItsWords;
use Modules\Kernel\Api\ARun;
use Modules\Kernel\Api\ARunAgreedTo;
use Modules\Kernel\Api\ARunPutBack;
use Modules\Kernel\Api\ARunToPutBack;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HearingTheJobs;
use Modules\Kernel\Api\History;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\PuttingARunBack;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\TheRecord;
use Modules\Kernel\Api\UndoSaysNothing;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\WhyTheWorkEnded;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\FollowsOneJobAtATime;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowARunBackReads;
use Modules\Operator\Internal\ViewModels\HowPuttingARunBackWent;
use Modules\Operator\Internal\ViewModels\WhatPuttingARunBackWouldShow;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

/**
 * Putting back one run the record shows, agreed to against the record's own rows.
 *
 * **The agreement is the record.** The stack takes no yes for this and offers
 * no rehearsal of it over the wire, so what the operator agrees to is what the
 * record already says of the run: every change it holds under the run's stamp,
 * and how many changes go with it. Where a row says it cannot be put back, the
 * stack would put none of the run back, and nothing is offered.
 *
 * **Putting it back answers a job,** followed by {@see FollowsOneJobAtATime}, as
 * putting a copy back is: while it runs the screen shows what the job says. The report leads with what was left and why,
 * because a run believed undone with part of it still standing is the machine
 * nobody has been told about. A rehearsal is labelled as one.
 *
 * **A run the stack would not put back is its answer, not a fault.** It is
 * drawn in the stack's words, apart from a stack that could not be reached,
 * and asking again is not offered, because the same work is answered the same
 * way. The road offered is back to the record, which says what stands now.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class PuttingThatRunBack extends NativeComponent implements AwaitsAnOutcome
{
    use AsksTheStackAgain;
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    /** @use FollowsOneJobAtATime<HowPuttingARunBackWent> */
    use FollowsOneJobAtATime, FindsItsWayAround {
        FollowsOneJobAtATime::letGoOfWhatElseItHears insteadof FindsItsWayAround;
    }
    use DrawsItsTemplate;

    public const string TEMPLATE = 'operator::putting-that-run-back';

    /**
     * What the record says of the run, once the frame has asked.
     *
     * `public` for {@see HowCurrentThisStackIs::$answered}'s reason.
     */
    public ?WhatPuttingARunBackWouldShow $answered = null;

    /**
     * The run as the record showed it, while there is one to agree to.
     *
     * Held as the value rather than as the fold, because a yes can only be
     * built from the rows themselves.
     */
    public ?ARunToPutBack $shown = null;

    /** Whether the operator has agreed, which is which of the two readings a frame draws. */
    public bool $agreed = false;

    public function __construct(
        private readonly History $history,
        private readonly PuttingARunBack $puttingBack,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        private readonly Clock $clock,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
        protected readonly HearingTheJobs $hearingTheJobs,
    ) {}

    /** The stamp of the run this screen is about, as the route carries it. */
    public function stampNamed(): string
    {
        return $this->around->aboutOn($this);
    }

    /** What the record says of the run, asked once per frame. */
    public function answer(): WhatPuttingARunBackWouldShow
    {
        return $this->answered ??= $this->read();
    }

    /** Whether the operator has agreed to put the run back. */
    public function wasAgreedTo(): bool
    {
        return $this->agreed;
    }

    /** What became of the yes, asked once per frame. */
    public function done(): HowPuttingARunBackWent
    {
        return $this->whatBecameOfTheJob();
    }

    /**
     * Put the run back, as the record showed it.
     *
     * Silent where no run is held that can go back: a frame that has not read
     * the record, a record holding nothing under the stamp, or one saying part
     * of the run cannot go back. There is nothing to agree to.
     */
    public function agree(): void
    {
        $shown = $this->shown;

        if (! $shown instanceof ARunToPutBack || ! $shown->goesBack()) {
            return;
        }

        $stack = $this->stack();
        $this->agreed = true;
        $this->forget();

        $this->reported($this->storage->resume($stack->id())->either(
            held: fn(Session $session): HowPuttingARunBackWent => $this->puttingBack->putBack($stack, $session, ARunAgreedTo::by($shown))->either(
                started: function (Job $job): HowPuttingARunBackWent {
                    $this->follow($job);

                    return new HowARunBackReads()->running();
                },
                met: $this->lettingGoIfRefused($stack, new HowARunBackReads()->met(...)),
            ),
            notHeld: static fn(): HowPuttingARunBackWent => new HowARunBackReads()->signedOut(),
        ));
    }

    /**
     * Ask again, because the operator said so.
     *
     * After a yes the stack took on, it asks after the same job. Otherwise
     * — before a yes, or after one the stack did not take on — it reads the record
     * afresh, since the record can move on and a refused yes put nothing back.
     */
    public function again(): void
    {
        $this->askAfterAgain();

        if ($this->agreed && $this->follows()) {
            return;
        }

        $this->agreed = false;
        $this->shown = null;
        $this->answered = null;
    }

    /** Whether the stack is putting the run back right now. */
    public function isWorking(): bool
    {
        return $this->agreed && $this->done()->stands->isWorking();
    }

    /** What became of the yes. */
    protected function whatTheJobCameTo(Stack $stack, Session $session, Job $job): HowPuttingARunBackWent
    {
        return $this->puttingBack->whatBecameOf($stack, $session, $job)->either(
            stillRunning: static fn(): HowPuttingARunBackWent => new HowARunBackReads()->running(),
            done: static fn(ARunPutBack $report): HowPuttingARunBackWent => new HowARunBackReads()->done($report),
            refused: static fn(ARefusalInItsWords $why): HowPuttingARunBackWent => new HowARunBackReads()->refused($why),
            ended: static fn(?WhyTheWorkEnded $why): HowPuttingARunBackWent => new HowARunBackReads()->ended($why),
            met: $this->lettingGoIfRefused($stack, new HowARunBackReads()->met(...)),
        );
    }

    protected function whileNoJobIsFollowed(): HowPuttingARunBackWent
    {
        return new HowARunBackReads()->ended(null);
    }

    protected function withNoSessionHeld(): HowPuttingARunBackWent
    {
        return new HowARunBackReads()->signedOut();
    }

    /** Resume the session and read what the record says of the run. */
    private function read(): WhatPuttingARunBackWouldShow
    {
        try {
            $run = ARun::stamped($this->stampNamed());
        } catch (UndoSaysNothing) {
            return new HowARunBackReads()->namesNoRun();
        }

        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatPuttingARunBackWouldShow => $this->theRun($stack, $session, $run),
            notHeld: static fn(): WhatPuttingARunBackWouldShow => new HowARunBackReads()->notAsked(),
        );
    }

    /** The record's rows for the run, held to agree against, or what the operator met instead. */
    private function theRun(Stack $stack, Session $session, ARun $run): WhatPuttingARunBackWouldShow
    {
        return $this->history->recordedOn($stack, $session)->either(
            record: function (TheRecord $record) use ($run): WhatPuttingARunBackWouldShow {
                $this->shown = $record->theRun($run);

                return new HowARunBackReads()->shown($this->shown, $this->clock->now());
            },
            met: $this->lettingGoIfRefused($stack, new HowARunBackReads()->notShown(...)),
        );
    }
}
