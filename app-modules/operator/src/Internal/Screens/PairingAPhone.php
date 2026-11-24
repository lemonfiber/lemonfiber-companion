<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\APairingCode;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Encoding;
use Modules\Kernel\Api\HearingTheJobs;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\LocalZone;
use Modules\Kernel\Api\MakingPairingCodes;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhatBecameOfThePairingCode;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\FollowsOneJobAtATime;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowThePairingCodeReads;
use Modules\Operator\Internal\ViewModels\HowThePairingCodeWent;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Edge\NativeComponent;

/**
 * Pairing another phone with this stack: a code it scans, or a line it types.
 *
 * **Nothing is asked on open.** A code is made when the operator taps for one,
 * because each is fresh material that stops being good minutes later, and one
 * made unasked would be pairing material on the glass that nobody wanted.
 *
 * **Making one answers a job**, followed through {@see FollowsOneJobAtATime}
 * while the stack makes it: the screen shows what the job says.
 *
 * **The code is held here and nowhere else.** It is pairing material: never
 * kept, never cached, never passed on by the device's sharing, and gone when
 * the screen is. Once its time has passed it is taken off the glass, and a new
 * one is a tap away; the cadence that notices is a look at the clock, not a
 * question to the stack.
 *
 * **A code nobody could check is not shown.** The phone that types the line
 * works the check code out from the fingerprint, so one the stack worked out
 * differently would never match; the code is let go of and the screen says
 * why, instead of handing out a line that fails at the other end.
 *
 * `Concealed` because a pairing code is on the glass.
 */
#[Lazy]
#[Concealed]
#[ItsContent(WhatItShowsDoes::ChangesOnItsOwn)]
final class PairingAPhone extends NativeComponent implements AwaitsAnOutcome
{
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use DrawsItsTemplate;
    /** @use FollowsOneJobAtATime<HowThePairingCodeWent> */
    use FollowsOneJobAtATime, FindsItsWayAround {
        FollowsOneJobAtATime::letGoOfWhatElseItHears insteadof FindsItsWayAround;
    }

    public const string TEMPLATE = 'operator::pairing-a-phone';

    /** The code the stack made, while this screen shows it. */
    private ?APairingCode $code = null;

    /** That code as the screen draws it, drawn once rather than on every look. */
    private ?HowThePairingCodeWent $drawn = null;

    public function __construct(
        private readonly MakingPairingCodes $pairing,
        private readonly Encoding $encoding,
        private readonly Clock $clock,
        private readonly LocalZone $here,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
        protected readonly HearingTheJobs $hearingTheJobs,
    ) {}

    /** Where making a code has got to, or that none was asked for: a code held is read against the clock on every look. */
    public function going(): HowThePairingCodeWent
    {
        $code = $this->code;

        return $code instanceof APairingCode ? $this->shown($code) : $this->whatBecameOfTheJob();
    }

    /** Ask the stack for a fresh code, letting go of any shown before it. */
    public function make(): void
    {
        $stack = $this->stack();
        $this->code = null;
        $this->drawn = null;
        $this->forget();

        $this->reported($this->storage->resume($stack->id())->either(
            held: fn(Session $session): HowThePairingCodeWent => $this->answered($this->pairing->make($stack, $session), $stack),
            notHeld: static fn(): HowThePairingCodeWent => new HowThePairingCodeReads()->signedOut(),
        ));
    }

    protected function whatTheJobCameTo(Stack $stack, Session $session, Job $job): HowThePairingCodeWent
    {
        return $this->answered($this->pairing->whatBecameOf($stack, $session, $job), $stack);
    }

    protected function whileNoJobIsFollowed(): HowThePairingCodeWent
    {
        return new HowThePairingCodeReads()->notAsked();
    }

    protected function withNoSessionHeld(): HowThePairingCodeWent
    {
        return new HowThePairingCodeReads()->signedOut();
    }

    /** What the stack answered, as the screen draws it, following the job it names or holding the code. */
    private function answered(WhatBecameOfThePairingCode $became, Stack $stack): HowThePairingCodeWent
    {
        return $became->either(
            underway: function (Job $job): HowThePairingCodeWent {
                $this->follow($job);

                return new HowThePairingCodeReads()->working();
            },
            made: function (APairingCode $code): HowThePairingCodeWent {
                if (! $code->agreesOnTheCheckCode()) {
                    return new HowThePairingCodeReads()->checkedDifferently();
                }

                $this->code = $code;

                return $this->shown($code);
            },
            ended: static fn(): HowThePairingCodeWent => new HowThePairingCodeReads()->notAsked(),
            refused: static fn(string $because): HowThePairingCodeWent => new HowThePairingCodeReads()->refused($because),
            met: function (Obstacle $why) use ($stack): HowThePairingCodeWent {
                $this->letGoOfTheSession($why, $stack);

                return new HowThePairingCodeReads()->met($why);
            },
        );
    }

    /** The code held, while it is good; once it is not, it is let go of. */
    private function shown(APairingCode $code): HowThePairingCodeWent
    {
        if ($code->hasExpiredBy($this->clock->now())) {
            $this->code = null;
            $this->drawn = null;

            return $this->reported(new HowThePairingCodeReads()->expired());
        }

        return $this->drawn ??= new HowThePairingCodeReads()->made($code, $this->encoding->codeFor($code->line()), $this->here->zone());
    }
}
