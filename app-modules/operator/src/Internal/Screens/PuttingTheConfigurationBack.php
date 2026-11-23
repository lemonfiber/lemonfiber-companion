<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\AResetAgreed;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HearingTheJobs;
use Modules\Kernel\Api\HowTheResetIsGoing;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\ResettingTheConfiguration;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\TheReset;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\FollowsOneJobAtATime;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowAResetReads;
use Modules\Operator\Internal\ViewModels\AResetAsShown;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

/**
 * Putting the stack's configuration back to lemonfiber's own, previewed first
 * and carried out only on a yes.
 *
 * **The preview comes first, and is labelled as one.** The stack compares the
 * operator's files and connections with lemonfiber's and writes nothing. The
 * screen draws every file that would go back, with the lines that change —
 * the operator's marked `-`, lemonfiber's `+` — and every connection that
 * would go with them. A preview that would change nothing says so and offers
 * nothing to agree to.
 *
 * **Only the preview can be agreed to.** The yes is an {@see AResetAgreed},
 * which only a preview that would revert something produces.
 *
 * **Both are jobs the stack names,** followed in turn by {@see FollowsOneJobAtATime}:
 * while each runs the screen shows what it says. The report after the yes says what went back and which connections
 * went with it, in the past tense only where the stack says it was carried out.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class PuttingTheConfigurationBack extends NativeComponent implements AwaitsAnOutcome
{
    use AsksTheStackAgain;
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    /** @use FollowsOneJobAtATime<AResetAsShown> */
    use FollowsOneJobAtATime, FindsItsWayAround {
        FollowsOneJobAtATime::letGoOfWhatElseItHears insteadof FindsItsWayAround;
    }
    use DrawsItsTemplate;

    public const string TEMPLATE = 'operator::putting-the-configuration-back';

    /**
     * The preview, while there is one to agree to.
     *
     * Held as the value rather than as the fold, because a yes can only be
     * built from the preview itself.
     */
    public ?TheReset $previewed = null;

    /** Whether the operator has agreed, which is which of the two jobs the screen follows. */
    public bool $agreed = false;

    public function __construct(
        private readonly ResettingTheConfiguration $resetting,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
        protected readonly HearingTheJobs $hearingTheJobs,
    ) {}

    /** The reading a frame draws: what the yes came to once it was given, and the preview until then. */
    public function answer(): AResetAsShown
    {
        return $this->agreed ? $this->done() : $this->whatBecameOfTheJob();
    }

    /** Whether the operator has agreed to the preview. */
    public function wasAgreedTo(): bool
    {
        return $this->agreed;
    }

    /** What became of the yes, asked once per frame; nothing to report before one. */
    public function done(): AResetAsShown
    {
        return $this->agreed ? $this->whatBecameOfTheJob() : HowAResetReads::afterTheYes()->ended(null);
    }

    /**
     * Put the configuration back, as previewed.
     *
     * Silent where no preview is held that would revert something: there is
     * nothing to agree to.
     */
    public function agree(): void
    {
        $previewed = $this->previewed;

        if (! $previewed instanceof TheReset || ! $previewed->mayBeAgreedTo()) {
            return;
        }

        $stack = $this->stack();
        $this->agreed = true;

        $this->reported($this->storage->resume($stack->id())->either(
            held: fn(Session $session): AResetAsShown => $this->resetting->revert($stack, $session, AResetAgreed::to($previewed))->either(
                started: function (Job $job): AResetAsShown {
                    $this->follow($job);

                    return HowAResetReads::afterTheYes()->running();
                },
                met: fn(Obstacle $why): AResetAsShown => $this->refusedBy($why, $stack, HowAResetReads::afterTheYes()),
            ),
            notHeld: static fn(): AResetAsShown => HowAResetReads::afterTheYes()->signedOut(),
        ));
    }

    /**
     * Ask again, because the operator said so.
     *
     * The job is kept only while it runs, on either side of the yes, because
     * reading it again is the only way to learn it has finished. Anything else
     * asks for the preview afresh: a finished preview can have moved on, a
     * refused yes put nothing back, and after a yes that finished or that the
     * stack no longer knows, a fresh preview is what says what still differs.
     */
    public function again(): void
    {
        if ($this->awaitsAnOutcome()) {
            $this->askAfterAgain();

            return;
        }

        $this->forget();
        $this->agreed = false;
        $this->previewed = null;
    }

    /** Whether the stack is at it right now. */
    public function isWorking(): bool
    {
        return $this->answer()->stands->isWorking();
    }

    /** What became of the preview, or of the yes once there is one. */
    protected function whatTheJobCameTo(Stack $stack, Session $session, Job $job): AResetAsShown
    {
        $going = $this->resetting->whatBecameOf($stack, $session, $job);

        return $this->agreed ? $this->carriedOutAs($going, $stack) : $this->previewedAs($going, $stack);
    }

    /**
     * Ask for the preview and follow the job it answers with; after a yes
     * with no job, there is nothing it did.
     */
    protected function whileNoJobIsFollowed(): AResetAsShown
    {
        if ($this->agreed) {
            return HowAResetReads::afterTheYes()->ended(null);
        }

        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): AResetAsShown => $this->resetting->wouldRevert($stack, $session)->either(
                started: function (Job $job) use ($stack, $session): AResetAsShown {
                    $this->follow($job);

                    return $this->previewedAs($this->resetting->whatBecameOf($stack, $session, $job), $stack);
                },
                met: fn(Obstacle $why): AResetAsShown => $this->refusedBy($why, $stack, HowAResetReads::beforeTheYes()),
            ),
            notHeld: static fn(): AResetAsShown => HowAResetReads::beforeTheYes()->signedOut(),
        );
    }

    protected function withNoSessionHeld(): AResetAsShown
    {
        return ($this->agreed ? HowAResetReads::afterTheYes() : HowAResetReads::beforeTheYes())->signedOut();
    }

    /** What became of the preview, held to agree against where the stack reported one. */
    private function previewedAs(HowTheResetIsGoing $going, Stack $stack): AResetAsShown
    {
        $reads = HowAResetReads::beforeTheYes();

        return $going->either(
            stillRunning: $reads->running(...),
            done: function (TheReset $reset) use ($reads): AResetAsShown {
                $this->previewed = $reset;

                return $reads->reported($reset);
            },
            refused: $reads->refused(...),
            ended: $reads->ended(...),
            met: fn(Obstacle $why): AResetAsShown => $this->refusedBy($why, $stack, $reads),
        );
    }

    /** What became of the yes. */
    private function carriedOutAs(HowTheResetIsGoing $going, Stack $stack): AResetAsShown
    {
        $reads = HowAResetReads::afterTheYes();

        return $going->either(
            stillRunning: $reads->running(...),
            done: $reads->reported(...),
            refused: $reads->refused(...),
            ended: $reads->ended(...),
            met: fn(Obstacle $why): AResetAsShown => $this->refusedBy($why, $stack, $reads),
        );
    }

    /** What the operator met, letting go of a session the stack refused. */
    private function refusedBy(Obstacle $why, Stack $stack, HowAResetReads $reads): AResetAsShown
    {
        $this->letGoOfTheSession($why, $stack);

        return $reads->met($why);
    }
}
