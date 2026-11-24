<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\ADownloadHeld;
use Modules\Kernel\Api\ADownloadLetGo;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HearingTheJobs;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StoppingSeeding;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\WhatLettingItGoCosts;
use Modules\Kernel\Api\WhyTheWorkEnded;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\FollowsAJob;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowLettingItGoReads;
use Modules\Operator\Internal\TheOfferAndItsYes;
use Modules\Operator\Internal\ViewModels\HowLettingItGoWent;
use Modules\Operator\Internal\ViewModels\WhatLettingItGoWouldShow;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function trim;

use UnitEnum;

/**
 * Stopping seeding one completed download, its cost stated first and carried out only on a yes.
 *
 * **Its own act, apart from removing anything else.** Stopping seeding is
 * asked about one download, by name, from the row the account of the disk
 * drew it on, and nothing else on this machine is touched by it.
 *
 * **The cost comes first.** The stack's offer says where the download stands,
 * the ratio it has reached, what it occupies, what removing it costs and what
 * goes with it, and the screen draws all of it before offering the yes.
 * Nothing on that frame is worded as having happened.
 *
 * **Only the offer can be agreed to.** The yes quotes the offer by the name
 * the stack gave it, so what is let go is what was shown; a stack that would
 * not offer offers nothing to agree to, and the screen offers nothing.
 *
 * **Both halves answer a handle,** followed on a declared cadence while either
 * runs, and never anything more: the cadence reads, and only the operator's
 * tap sends the yes. A rehearsed report is labelled as one and says nothing
 * was freed.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class LettingADownloadGo extends NativeComponent implements AwaitsAnOutcome
{
    use AsksTheStackAgain;
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    /** @use FollowsAJob<WhatLettingItGoWouldShow|HowLettingItGoWent> */
    use FollowsAJob, FindsItsWayAround {
        FollowsAJob::letGoOfWhatElseItHears insteadof FindsItsWayAround;
    }
    use DrawsItsTemplate;

    public const string TEMPLATE = 'operator::letting-a-download-go';


    /**
     * The offer, while there is one to agree to.
     *
     * Held as the value rather than as the fold, because a yes can only be
     * sent against the offer itself.
     */
    public ?WhatLettingItGoCosts $offered = null;

    /** Whether the operator has agreed, which is which of the two readings a frame draws. */
    public bool $agreed = false;


    public function __construct(
        private readonly StoppingSeeding $stopping,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
        protected readonly HearingTheJobs $hearingTheJobs,
    ) {}


    /** The download this screen is about, by the name the route carries. */
    public function downloadNamed(): string
    {
        return $this->around->aboutOn($this);
    }

    /** What stopping seeding would cost, asked once per frame. */
    public function answer(): WhatLettingItGoWouldShow
    {
        $report = $this->whatBecameOfTheJob(TheOfferAndItsYes::TheOffer);

        return $report instanceof WhatLettingItGoWouldShow ? $report : new HowLettingItGoReads()->notAsked();
    }

    /** Whether the operator has agreed to the offer. */
    public function wasAgreedTo(): bool
    {
        return $this->agreed;
    }

    /** What became of the yes, asked once per frame. */
    public function done(): HowLettingItGoWent
    {
        $report = $this->whatBecameOfTheJob(TheOfferAndItsYes::TheYes);

        return $report instanceof HowLettingItGoWent ? $report : new HowLettingItGoReads()->ended(null);
    }

    /**
     * Stop seeding the download as it was offered.
     *
     * Silent where no offer is held, which is a frame that has not read one or
     * a stack that would not offer: there is nothing to agree to.
     */
    public function agree(): void
    {
        $offer = $this->offered;

        if (! $offer instanceof WhatLettingItGoCosts) {
            return;
        }

        $stack = $this->stack();
        $this->agreed = true;
        $this->forget(TheOfferAndItsYes::TheYes);
        $this->reported($this->storage->resume($stack->id())->either(
            held: fn(Session $session): HowLettingItGoWent => $this->stopping->stop($stack, $session, $offer)->either(
                started: function (Job $job): HowLettingItGoWent {
                    $this->follow($job, TheOfferAndItsYes::TheYes);

                    return new HowLettingItGoReads()->running();
                },
                met: $this->lettingGoIfRefused($stack, new HowLettingItGoReads()->met(...)),
            ),
            notHeld: static fn(): HowLettingItGoWent => new HowLettingItGoReads()->signedOut(),
        ), TheOfferAndItsYes::TheYes);
    }

    /**
     * Ask again, because the operator said so.
     *
     * After a yes the stack took on, it asks after the same handle. Before a
     * yes it reads the same asking while that is still being worked out, and
     * otherwise asks for the offer afresh — an offer can move on, a ratio
     * earned in the gap is a different offer, and a refused yes let nothing go.
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

    protected function whatBecameOfIt(Stack $stack, Session $session, Job $job, UnitEnum $for): WhatLettingItGoWouldShow|HowLettingItGoWent
    {
        return match (TheOfferAndItsYes::asked($for)) {
            TheOfferAndItsYes::TheOffer => $this->offer($stack, $session, $job),
            TheOfferAndItsYes::TheYes => $this->yesCameTo($stack, $session, $job),
        };
    }

    protected function whileNothingIsFollowed(UnitEnum $for): WhatLettingItGoWouldShow|HowLettingItGoWent
    {
        return match (TheOfferAndItsYes::asked($for)) {
            TheOfferAndItsYes::TheOffer => $this->ask(),
            TheOfferAndItsYes::TheYes => new HowLettingItGoReads()->ended(null),
        };
    }

    protected function withoutASession(UnitEnum $for): WhatLettingItGoWouldShow|HowLettingItGoWent
    {
        return match (TheOfferAndItsYes::asked($for)) {
            TheOfferAndItsYes::TheOffer => new HowLettingItGoReads()->notAsked(),
            TheOfferAndItsYes::TheYes => new HowLettingItGoReads()->signedOut(),
        };
    }

    /** Resume the session and ask what stopping seeding the download would cost. */
    private function ask(): WhatLettingItGoWouldShow
    {
        $named = $this->downloadNamed();

        if (trim($named) === '') {
            return new HowLettingItGoReads()->namesNoDownload();
        }

        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): WhatLettingItGoWouldShow => $this->read($stack, $session, ADownloadHeld::named($named)),
            notHeld: static fn(): WhatLettingItGoWouldShow => new HowLettingItGoReads()->notAsked(),
        );
    }

    /** Ask what letting it go would cost, and follow the asking it starts. */
    private function read(Stack $stack, Session $session, ADownloadHeld $download): WhatLettingItGoWouldShow
    {

        return $this->stopping->whatItWouldCost($stack, $session, $download)->either(
            started: function (Job $job) use ($stack, $session): WhatLettingItGoWouldShow {
                $this->follow($job, TheOfferAndItsYes::TheOffer);


                return $this->offer($stack, $session, $job);
            },
            met: $this->lettingGoIfRefused($stack, new HowLettingItGoReads()->notOffered(...)),
        );
    }

    /** What the asking came to, the offer held to agree against. */
    private function offer(Stack $stack, Session $session, Job $job): WhatLettingItGoWouldShow
    {
        return $this->stopping->whatTheOfferCameTo($stack, $session, $job)->either(
            stillRunning: static fn(): WhatLettingItGoWouldShow => new HowLettingItGoReads()->stillWorkingItOut(),
            offering: function (WhatLettingItGoCosts $offer): WhatLettingItGoWouldShow {
                $this->offered = $offer;

                return new HowLettingItGoReads()->offering($offer);
            },
            ended: static fn(?WhyTheWorkEnded $why): WhatLettingItGoWouldShow => new HowLettingItGoReads()->offerEnded($why),
            met: $this->lettingGoIfRefused($stack, new HowLettingItGoReads()->notOffered(...)),
        );
    }

    /** Ask the stack what became of the yes. */
    private function yesCameTo(Stack $stack, Session $session, Job $job): HowLettingItGoWent
    {
        return $this->stopping->whatBecameOf($stack, $session, $job)->either(
            stillRunning: static fn(): HowLettingItGoWent => new HowLettingItGoReads()->running(),
            done: static fn(ADownloadLetGo $report): HowLettingItGoWent => new HowLettingItGoReads()->done($report),
            ended: static fn(?WhyTheWorkEnded $why): HowLettingItGoWent => new HowLettingItGoReads()->ended($why),
            met: $this->lettingGoIfRefused($stack, new HowLettingItGoReads()->met(...)),
        );
    }
}
