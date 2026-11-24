<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\AHandoff;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Encoding;
use Modules\Kernel\Api\HandingOverADevice;
use Modules\Kernel\Api\HearingTheJobs;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\SomebodyInTheHousehold;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhatBecameOfTheHandoff;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\FollowsOneJobAtATime;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowTheHandoffReads;
use Modules\Operator\Internal\TheCodesDrawn;
use Modules\Operator\Internal\ViewModels\HowTheHandoffWent;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

use function trim;

/**
 * Connecting one member's device: the code that points it at the media server, and whether it has signed in.
 *
 * **Nothing is asked on open.** The first asking writes down when the code was
 * given and which devices were signed in then, so it is the operator's tap
 * that starts the hand-off, never the screen appearing.
 *
 * **Asking answers a job**, followed through {@see FollowsOneJobAtATime}
 * while the stack works it out: the screen shows what the job says.
 * Once the stack has said, nothing is asked again until the operator taps:
 * whether their device has signed in is read when they say it has.
 *
 * Not `Concealed`: the code is the server's address and nothing more, which
 * signs nobody in.
 */
#[Lazy]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class ConnectingADeviceForThem extends NativeComponent implements AwaitsAnOutcome
{
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use DrawsItsTemplate;
    /** @use FollowsOneJobAtATime<HowTheHandoffWent> */
    use FollowsOneJobAtATime, FindsItsWayAround {
        FollowsOneJobAtATime::letGoOfWhatElseItHears insteadof FindsItsWayAround;
    }

    public const string TEMPLATE = 'operator::connecting-a-device';

    public function __construct(
        private readonly HandingOverADevice $handing,
        private readonly Encoding $encoding,
        private readonly Clock $clock,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
        protected readonly HearingTheJobs $hearingTheJobs,
    ) {}

    /** The member this screen is about, by the name the route carries. */
    public function named(): string
    {
        return $this->around->aboutOn($this);
    }

    /** Where the hand-off has got to, or that nothing was asked yet. */
    public function going(): HowTheHandoffWent
    {
        return $this->whatBecameOfTheJob();
    }

    /** Ask the stack where handing their device over stands, letting go of what it said before. */
    public function show(): void
    {
        $this->forget();

        if (trim($this->named()) === '') {
            $this->reported(new HowTheHandoffReads()->notAsked());

            return;
        }

        $stack = $this->stack();
        $who = SomebodyInTheHousehold::called($this->named());

        $this->reported($this->storage->resume($stack->id())->either(
            held: fn(Session $session): HowTheHandoffWent => $this->answered($this->handing->handOver($stack, $session, $who), $stack),
            notHeld: static fn(): HowTheHandoffWent => new HowTheHandoffReads()->signedOut(),
        ));
    }

    protected function whatTheJobCameTo(Stack $stack, Session $session, Job $job): HowTheHandoffWent
    {
        return $this->answered($this->handing->whatBecameOf($stack, $session, $job), $stack);
    }

    protected function whileNoJobIsFollowed(): HowTheHandoffWent
    {
        return new HowTheHandoffReads()->notAsked();
    }

    protected function withNoSessionHeld(): HowTheHandoffWent
    {
        return new HowTheHandoffReads()->signedOut();
    }

    /** What the stack answered, as the screen draws it, following the job it names. */
    private function answered(WhatBecameOfTheHandoff $became, Stack $stack): HowTheHandoffWent
    {
        return $became->either(
            underway: function (Job $job): HowTheHandoffWent {
                $this->follow($job);

                return new HowTheHandoffReads()->working();
            },
            answered: fn(AHandoff $handoff): HowTheHandoffWent => new HowTheHandoffReads()->answered(
                $handoff,
                $this->codesOf($handoff),
                $this->clock->now(),
            ),
            ended: static fn(): HowTheHandoffWent => new HowTheHandoffReads()->notAsked(),
            refused: static fn(string $because): HowTheHandoffWent => new HowTheHandoffReads()->refused($because),
            met: function (Obstacle $why) use ($stack): HowTheHandoffWent {
                $this->letGoOfTheSession($why, $stack);

                return new HowTheHandoffReads()->met($why);
            },
        );
    }

    /** A code for the address, and for every app whose code is a link that opens it at this server. */
    private function codesOf(AHandoff $handoff): TheCodesDrawn
    {
        $handed = [$handoff->handed()->address()];

        foreach ($handoff->handed()->clients() as $client) {
            if ($client->isALink()) {
                $handed[] = $client;
            }
        }

        return TheCodesDrawn::of($this->encoding, ...$handed);
    }
}
