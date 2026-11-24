<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HearingTheJobs;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\PausingDownloads;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\WhatPausingComesTo;
use Modules\Kernel\Api\WhatToDoWithTheDownloads;
use Modules\Kernel\Api\WhyTheWorkEnded;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\FollowsAJob;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowPausingReads;
use Modules\Operator\Internal\TheOfferAndItsYes;
use Modules\Operator\Internal\ViewModels\HowPausingWent;
use Modules\Operator\Internal\ViewModels\WhatPausingSays;
use Modules\Operator\Internal\ViewModels\WhatPausingWouldShow;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;
use UnitEnum;

/**
 * Pausing every download client, or letting them fetch again, rehearsed first and carried out only on a yes.
 *
 * **The rehearsal comes first.** It asks no client anything and says what each
 * is doing now, which client could not be reached and why, and, on a resume,
 * that a spent cap will stop them again. Nothing on that frame is worded as
 * having happened.
 *
 * **Only the rehearsal can be agreed to.** The yes quotes the offer by the name
 * the stack gave it, so the clients asked are the ones shown in the states
 * shown; a stack where any has moved since refuses it.
 *
 * **Both halves answer a handle,** followed on a declared cadence while either
 * runs, and only the operator's tap sends the yes. What happened is drawn from
 * what each client read back, before and after, and a client the stack could
 * not reach is named with why.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class PausingTheDownloads extends NativeComponent implements AwaitsAnOutcome
{
    use AsksTheStackAgain;
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    /** @use FollowsAJob<WhatPausingWouldShow|HowPausingWent> */
    use FollowsAJob, FindsItsWayAround {
        FollowsAJob::letGoOfWhatElseItHears insteadof FindsItsWayAround;
    }
    use DrawsItsTemplate;

    public const string TEMPLATE = 'operator::pausing-the-downloads';


    /** The rehearsal's report, while there is one to agree to, held as the value because a yes can only quote the offer itself. */
    public ?WhatPausingComesTo $offered = null;

    /** Whether the operator has agreed, which is which of the two readings a frame draws. */
    public bool $agreed = false;

    public function __construct(
        private readonly PausingDownloads $pausing,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
        protected readonly HearingTheJobs $hearingTheJobs,
    ) {}


    /** Which of pausing and resuming this screen is about, by the word the route carries, or none. */
    public function whatWasAsked(): ?WhatToDoWithTheDownloads
    {
        return WhatToDoWithTheDownloads::tryFrom($this->around->aboutOn($this));
    }

    /** The lines that differ between pausing and resuming; pausing's where the route names neither, which draws none of them. */
    public function says(): WhatPausingSays
    {
        return new HowPausingReads()->lines($this->whatWasAsked() ?? WhatToDoWithTheDownloads::Pause);
    }

    /** What the rehearsal came to, asked once per frame. */
    public function answer(): WhatPausingWouldShow
    {
        $report = $this->whatBecameOfTheJob(TheOfferAndItsYes::TheOffer);

        return $report instanceof WhatPausingWouldShow ? $report : new HowPausingReads()->notAsked();
    }

    /** Whether the operator has agreed to the rehearsal. */
    public function wasAgreedTo(): bool
    {
        return $this->agreed;
    }

    /** What became of the yes, asked once per frame. */
    public function done(): HowPausingWent
    {
        $report = $this->whatBecameOfTheJob(TheOfferAndItsYes::TheYes);

        return $report instanceof HowPausingWent ? $report : new HowPausingReads()->ended(null);
    }

    /**
     * Carry out what the rehearsal offered.
     *
     * Silent where no rehearsal is held, which is a frame that has not read one
     * or a stack that would not rehearse: there is nothing to agree to.
     */
    public function agree(): void
    {
        $offer = $this->offered;

        if (! $offer instanceof WhatPausingComesTo || ! $offer->hasClients()) {
            return;
        }

        $stack = $this->stack();
        $this->agreed = true;
        $this->forget(TheOfferAndItsYes::TheYes);
        $this->reported($this->storage->resume($stack->id())->either(
            held: fn(Session $session): HowPausingWent => $this->pausing->carryOut($stack, $session, $offer)->either(
                started: function (Job $job): HowPausingWent {
                    $this->follow($job, TheOfferAndItsYes::TheYes);

                    return new HowPausingReads()->running();
                },
                met: $this->lettingGoIfRefused($stack, new HowPausingReads()->met(...)),
            ),
            notHeld: static fn(): HowPausingWent => new HowPausingReads()->signedOut(),
        ), TheOfferAndItsYes::TheYes);
    }

    /**
     * Ask again, because the operator said so.
     *
     * After a yes the stack took on, it asks after the same handle. Before a
     * yes it reads the same rehearsal while that is still running, and
     * otherwise rehearses afresh: a client may have stopped or started in the
     * gap, which is a different offer.
     */
    public function again(): void
    {
        if ($this->agreed && $this->follows(TheOfferAndItsYes::TheYes)) {
            $this->askAfterAgain(TheOfferAndItsYes::TheYes);

            return;
        }

        if (! $this->awaitsAnOutcome(TheOfferAndItsYes::TheOffer)) {
            $this->forget(TheOfferAndItsYes::TheOffer);
        }

        $this->askAfterAgain(TheOfferAndItsYes::TheOffer);

        $this->forget(TheOfferAndItsYes::TheYes);
        $this->agreed = false;
        $this->offered = null;
    }

    protected function whatBecameOfIt(Stack $stack, Session $session, Job $job, UnitEnum $for): WhatPausingWouldShow|HowPausingWent
    {
        return match (TheOfferAndItsYes::asked($for)) {
            TheOfferAndItsYes::TheOffer => $this->offer($stack, $session, $job),
            TheOfferAndItsYes::TheYes => $this->yesCameTo($stack, $session, $job),
        };
    }

    protected function whileNothingIsFollowed(UnitEnum $for): WhatPausingWouldShow|HowPausingWent
    {
        return match (TheOfferAndItsYes::asked($for)) {
            TheOfferAndItsYes::TheOffer => $this->ask(),
            TheOfferAndItsYes::TheYes => new HowPausingReads()->ended(null),
        };
    }

    protected function withoutASession(UnitEnum $for): WhatPausingWouldShow|HowPausingWent
    {
        return match (TheOfferAndItsYes::asked($for)) {
            TheOfferAndItsYes::TheOffer => new HowPausingReads()->notAsked(),
            TheOfferAndItsYes::TheYes => new HowPausingReads()->signedOut(),
        };
    }

    /** Resume the session and rehearse what was asked. */
    private function ask(): WhatPausingWouldShow
    {
        $asked = $this->whatWasAsked();

        if (! $asked instanceof WhatToDoWithTheDownloads) {
            return new HowPausingReads()->namesNothing();
        }

        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatPausingWouldShow => $this->read($stack, $session, $asked),
            notHeld: static fn(): WhatPausingWouldShow => new HowPausingReads()->notAsked(),
        );
    }

    /** Rehearse what was asked, and follow the rehearsal it starts. */
    private function read(Stack $stack, Session $session, WhatToDoWithTheDownloads $asked): WhatPausingWouldShow
    {

        return $this->pausing->rehearse($stack, $session, $asked)->either(
            started: function (Job $job) use ($stack, $session): WhatPausingWouldShow {
                $this->follow($job, TheOfferAndItsYes::TheOffer);


                return $this->offer($stack, $session, $job);
            },
            met: $this->lettingGoIfRefused($stack, new HowPausingReads()->notOffered(...)),
        );
    }

    /** What the rehearsal came to, its report held to agree against. */
    private function offer(Stack $stack, Session $session, Job $job): WhatPausingWouldShow
    {
        return $this->pausing->whatBecameOf($stack, $session, $job)->either(
            stillRunning: static fn(): WhatPausingWouldShow => new HowPausingReads()->stillWorkingItOut(),
            answered: function (WhatPausingComesTo $offer): WhatPausingWouldShow {
                $this->offered = $offer;

                return new HowPausingReads()->offering($offer);
            },
            ended: static fn(?WhyTheWorkEnded $why): WhatPausingWouldShow => new HowPausingReads()->offerEnded($why),
            met: $this->lettingGoIfRefused($stack, new HowPausingReads()->notOffered(...)),
        );
    }

    /** Ask the stack what became of the yes. */
    private function yesCameTo(Stack $stack, Session $session, Job $job): HowPausingWent
    {
        return $this->pausing->whatBecameOf($stack, $session, $job)->either(
            stillRunning: static fn(): HowPausingWent => new HowPausingReads()->running(),
            answered: static fn(WhatPausingComesTo $report): HowPausingWent => new HowPausingReads()->done($report),
            ended: static fn(?WhyTheWorkEnded $why): HowPausingWent => new HowPausingReads()->ended($why),
            met: $this->lettingGoIfRefused($stack, new HowPausingReads()->met(...)),
        );
    }
}
