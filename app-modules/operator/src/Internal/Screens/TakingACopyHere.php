<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;
use Modules\Design\Api\WritesItsTaps;
use Modules\Kernel\Api\ACopyAsked;
use Modules\Kernel\Api\ACopyTaken;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HearingTheJobs;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Supervising;
use Modules\Kernel\Api\TakingCopies;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\WhyTheWorkEnded;
use Modules\Operator\Internal\AsksWhatTheStackIsRunning;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\FollowsOneJobAtATime;
use Modules\Operator\Internal\FollowsTheCopyRunning;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowACopyReads;
use Modules\Operator\Internal\Presenters\HowAScopeReads;
use Modules\Operator\Internal\ViewModels\AScopeAsShown;
use Modules\Operator\Internal\ViewModels\HowTheCopyWent;
use Modules\Operator\Internal\ViewModels\WhatThisStackRunsTurnedOutToBe;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function trim;
use function view;

/**
 * Taking a copy of this machine's stack: of all of it, or of one service.
 *
 * **The scope is named before it starts and again on the result.** Choosing
 * one asks first, naming what the copy would cover; only the yes sends it.
 * The report says what it covered, which older copies it removed and how its
 * size stood against the minute a copy is meant to take.
 *
 * **Taking a copy answers a job,** followed by {@see FollowsOneJobAtATime}: while the
 * stack takes the copy the screen shows what the job says, and the report is
 * drawn once it finishes. How far a copy has got while it runs is the stack's
 * reading of whichever copy is running, followed by {@see FollowsTheCopyRunning}.
 *
 * The services a copy can be narrowed to are the ones the stack says it runs,
 * read once per frame.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class TakingACopyHere extends NativeComponent implements AwaitsAnOutcome
{
    use WritesItsTaps;
    use AsksTheStackAgain;
    use OffersTheAppsSettings;
    use AsksWhatTheStackIsRunning;
    /** @use FollowsOneJobAtATime<HowTheCopyWent> */
    use FollowsOneJobAtATime, FindsItsWayAround {
        FollowsOneJobAtATime::letGoOfWhatElseItHears insteadof FindsItsWayAround;
    }
    use FollowsTheCopyRunning;

    public const string TEMPLATE = 'operator::taking-a-copy-here';

    /**
     * The copy being asked about, while the operator decides.
     *
     * `public` for {@see HowCurrentThisStackIs::$answered}'s reason, and held
     * so the copy sent is the one the question named.
     */
    public ?ACopyAsked $asking = null;

    /** The copy that was sent, so what it covers can be said while it runs. */
    public ?ACopyAsked $taking = null;

    public function __construct(
        private readonly TakingCopies $copying,
        private readonly Supervising $supervising,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
        protected readonly HearingTheJobs $hearingTheJobs,
    ) {}

    /** The services a copy can be narrowed to, asked once per frame. */
    public function answer(): WhatThisStackRunsTurnedOutToBe
    {
        if (! $this->answered instanceof WhatThisStackRunsTurnedOutToBe) {
            $this->readsItsStack();
        }

        return $this->answered ??= $this->askWhatIsRunning($this->stack(), $this->storage, $this->supervising);
    }

    /** Draw the screen, a frame at a time. */
    public function render(): View
    {
        $this->aFrameBegins();

        return view(self::TEMPLATE);
    }

    /** Ask about a copy of the whole stack. */
    public function copyTheWholeStack(): void
    {
        $this->asking = ACopyAsked::ofTheWholeStack();
    }

    /**
     * Ask about a copy of one of the services the stack listed.
     *
     * Silent for a name the listing did not carry: a copy is only ever of a
     * service this screen showed, and a blank names none.
     */
    public function copyTheService(string $named): void
    {
        if (trim($named) === '') {
            return;
        }

        $asked = ServiceId::called($named);

        foreach ($this->answer()->services as $service) {
            if ($service->id->isTheSameAs($asked)) {
                $this->asking = ACopyAsked::ofOneService($service->id);

                return;
            }
        }
    }

    /** What the copy being asked about would cover, where one is. */
    public function askingAbout(): ?AScopeAsShown
    {
        return $this->asking instanceof ACopyAsked ? new HowAScopeReads()->of($this->asking->scope()) : null;
    }

    /** Take the copy that was asked about. */
    public function agree(): void
    {
        $asked = $this->asking;

        if (! $asked instanceof ACopyAsked) {
            return;
        }

        $this->asking = null;
        $this->takeIt($asked);
    }

    /** Leave it. */
    public function neverMind(): void
    {
        $this->asking = null;
    }

    /** What became of the copy taken here, or that none was. */
    public function lastCopy(): HowTheCopyWent
    {
        return $this->whatBecameOfTheJob();
    }

    /** Ask the stack again: the services, what became of the copy, and what is being copied now. */
    public function again(): void
    {
        $this->answered = null;
        $this->copyHeard = null;
        $this->askAfterAgain();
    }

    /** What became of the copy taken, or that none was where the copy sent is not held. */
    protected function whatTheJobCameTo(Stack $stack, Session $session, Job $job): HowTheCopyWent
    {
        $asked = $this->taking;

        if (! $asked instanceof ACopyAsked) {
            return new HowACopyReads()->notAsked();
        }

        return $this->copying->whatBecameOf($stack, $session, $job)->either(
            stillRunning: static fn(): HowTheCopyWent => new HowACopyReads()->running($asked->scope()),
            done: static fn(ACopyTaken $report): HowTheCopyWent => new HowACopyReads()->done($report),
            ended: static fn(?WhyTheWorkEnded $why): HowTheCopyWent => new HowACopyReads()->ended($why, $asked->scope()),
            met: $this->lettingGoIfRefused($stack, new HowACopyReads()->met(...)),
        );
    }

    protected function letGoOfWhatElseItHears(): void
    {
        $this->hearingTheJobs->letGo();
        $this->letGoOfTheCopy();
    }

    protected function whileNoJobIsFollowed(): HowTheCopyWent
    {
        return new HowACopyReads()->notAsked();
    }

    protected function withNoSessionHeld(): HowTheCopyWent
    {
        return new HowACopyReads()->signedOut();
    }

    /**
     * Send the copy agreed to, and follow the job it answers with.
     *
     * A refusal is kept as what became of it, so the screen says what stood
     * in the way rather than carrying on as though a copy were being taken.
     */
    private function takeIt(ACopyAsked $asked): void
    {
        $stack = $this->stack();
        $this->forget();
        $this->taking = $asked;

        $this->reported($this->storage->resume($stack->id())->either(
            held: fn(Session $session): HowTheCopyWent => $this->copying->take($stack, $session, $asked)->either(
                started: function (Job $job) use ($asked): HowTheCopyWent {
                    $this->follow($job);

                    return new HowACopyReads()->running($asked->scope());
                },
                met: $this->lettingGoIfRefused($stack, new HowACopyReads()->met(...)),
            ),
            notHeld: static fn(): HowTheCopyWent => new HowACopyReads()->signedOut(),
        ));
    }
}
