<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;

use function is_string;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\AHandoff;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Encoding;
use Modules\Kernel\Api\HandingOverADevice;
use Modules\Kernel\Api\HowOftenAScreenLooks;
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
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowTheHandoffReads;
use Modules\Operator\Internal\ViewModels\HowTheHandoffWent;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

use function trim;
use function view;

/**
 * Connecting one member's device: the code that points it at the media server, and whether it has signed in.
 *
 * **Nothing is asked on open.** The first asking writes down when the code was
 * given and which devices were signed in then, so it is the operator's tap
 * that starts the hand-off, never the screen appearing.
 *
 * **Asking answers a handle**, held and asked after on a declared cadence
 * while the stack works it out, the way {@see PairingAPhone} follows a code.
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
    use FindsItsWayAround;

    /** The handle of the hand-off being worked out, while there is one. */
    public ?string $following = null;

    /** Where it has got to, once this frame has asked. Public for {@see WhatIsRunningHere::$answered}'s reason. */
    public ?HowTheHandoffWent $going = null;

    /** Where the stack last said it stands, until it is asked again. */
    private ?AHandoff $handoff = null;

    /** That answer as the screen draws it, drawn once rather than on every look. */
    private ?HowTheHandoffWent $drawn = null;

    public function __construct(
        private readonly HandingOverADevice $handing,
        private readonly Encoding $encoding,
        private readonly Clock $clock,
        private readonly SecureStorage $storage,
        private readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
    ) {}



    /** The member this screen is about, by the name the route carries. */
    public function named(): string
    {
        $named = $this->param('service');

        return is_string($named) ? $named : '';
    }

    public function render(): View
    {
        return view('operator::connecting-a-device');
    }

    /** Where the hand-off has got to, or that nothing was asked yet. */
    public function going(): HowTheHandoffWent
    {
        return $this->going ??= $this->followed();
    }

    /** Ask the stack where handing their device over stands, letting go of what it said before. */
    public function show(): void
    {
        $this->handoff = null;
        $this->drawn = null;
        $this->following = null;

        if (trim($this->named()) === '') {
            $this->going = new HowTheHandoffReads()->notAsked();

            return;
        }

        $stack = $this->stack();
        $who = SomebodyInTheHousehold::called($this->named());

        $this->going = $this->storage->resume($stack->id())->either(
            held: fn(Session $session): HowTheHandoffWent => $this->answered($this->handing->handOver($stack, $session, $who), $stack),
            notHeld: static fn(): HowTheHandoffWent => new HowTheHandoffReads()->signedOut(),
        );
    }

    /**
     * Look again while the stack works the hand-off out.
     *
     * While it does, this asks the stack after it; once it has said, this asks
     * nothing. The interval is {@see HowOftenAScreenLooks}'s constant.
     */
    #[Poll(HowOftenAScreenLooks::WHILE_WORK_RUNS_MS)]
    public function whileItRuns(): void
    {
        $this->going = null;
    }

    /** Whether the stack still held a handle for the work when it last answered. */
    public function awaitsAnOutcome(): bool
    {
        return $this->following !== null;
    }

    /** Draw the answer held, or ask after the hand-off being worked out. */
    private function followed(): HowTheHandoffWent
    {
        $handoff = $this->handoff;

        return match (true) {
            $handoff instanceof AHandoff => $this->shown($handoff),
            $this->following === null => new HowTheHandoffReads()->notAsked(),
            default => $this->askedAfter($this->following),
        };
    }

    /** Ask the stack what became of the hand-off being worked out. */
    private function askedAfter(string $following): HowTheHandoffWent
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): HowTheHandoffWent => $this->answered($this->handing->whatBecameOf($stack, $session, Job::named($following)), $stack),
            notHeld: static fn(): HowTheHandoffWent => new HowTheHandoffReads()->signedOut(),
        );
    }

    /** What the stack answered, as the screen draws it, holding the handle or the answer. */
    private function answered(WhatBecameOfTheHandoff $became, Stack $stack): HowTheHandoffWent
    {
        return $became->either(
            underway: function (Job $job): HowTheHandoffWent {
                $this->following = $job->shown();

                return new HowTheHandoffReads()->working();
            },
            answered: function (AHandoff $handoff): HowTheHandoffWent {
                $this->following = null;
                $this->handoff = $handoff;

                return $this->shown($handoff);
            },
            ended: function (): HowTheHandoffWent {
                $this->following = null;

                return new HowTheHandoffReads()->notAsked();
            },
            refused: function (string $because): HowTheHandoffWent {
                $this->following = null;

                return new HowTheHandoffReads()->refused($because);
            },
            met: function (Obstacle $why) use ($stack): HowTheHandoffWent {
                $this->following = null;
                $this->letGoOfTheSession($why, $stack);

                return new HowTheHandoffReads()->met($why);
            },
        );
    }

    /** The answer held, drawn once. */
    private function shown(AHandoff $handoff): HowTheHandoffWent
    {
        return $this->drawn ??= new HowTheHandoffReads()->answered(
            $handoff,
            $this->encoding->codeFor($handoff->handed()->address()),
            $this->clock->now(),
        );
    }
}
