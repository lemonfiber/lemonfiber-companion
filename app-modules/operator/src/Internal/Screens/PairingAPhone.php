<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;
use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\APairingCode;
use Modules\Kernel\Api\Clock;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\Encoding;
use Modules\Kernel\Api\HowOftenAScreenLooks;
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
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowThePairingCodeReads;
use Modules\Operator\Internal\ViewModels\HowThePairingCodeWent;
use Modules\Operator\Internal\WhereAStackIs;
use Modules\Wayfinding\Api\TheWayAround;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * Pairing another phone with this stack: a code it scans, or a line it types.
 *
 * **Nothing is asked on open.** A code is made when the operator taps for one,
 * because each is fresh material that stops being good minutes later, and one
 * made unasked would be pairing material on the glass that nobody wanted.
 *
 * **Making one answers a handle**, held and asked after on a declared cadence
 * while the stack makes it, the way {@see TakingACopyHere} follows a copy.
 *
 * **The code is held here and nowhere else.** It is pairing material: never
 * kept, never cached, never passed on by the device's sharing, and gone when
 * the screen is. Once its time has passed it is taken off the glass, and a new
 * one is a tap away; the cadence that notices is a look at the clock, not a
 * question to the stack.
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
    use FindsItsWayAround;

    /** The handle of the code being made, while there is one. Never kept past this screen. */
    public ?string $following = null;

    /** Where it has got to, once this frame has asked. Public for {@see WhatIsRunningHere::$answered}'s reason. */
    public ?HowThePairingCodeWent $going = null;

    /** Whether the code last shown here stopped being good, until another is asked for. */
    public bool $lapsed = false;

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
        private readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
    ) {}

    /** The stack this screen is about, read from the route on every frame. */
    public function stack(): Stack
    {
        return $this->around->stackOn($this);
    }

    /** Where this machine's screens are. */
    public function goes(): WhereAStackIs
    {
        return WhereAStackIs::of($this->stack()->id());
    }

    public function render(): View
    {
        return view('operator::pairing-a-phone');
    }

    /** Where making a code has got to, or that none was asked for. */
    public function going(): HowThePairingCodeWent
    {
        return $this->going ??= $this->followed();
    }

    /** Ask the stack for a fresh code, letting go of any shown before it. */
    public function make(): void
    {
        $stack = $this->stack();
        $this->code = null;
        $this->drawn = null;
        $this->following = null;
        $this->lapsed = false;

        $this->going = $this->storage->resume($stack->id())->either(
            held: fn(Session $session): HowThePairingCodeWent => $this->answered($this->pairing->make($stack, $session), $stack),
            notHeld: static fn(): HowThePairingCodeWent => new HowThePairingCodeReads()->signedOut(),
        );
    }

    /**
     * Look again while the stack makes the code, and while one is shown.
     *
     * While it is made this asks the stack after it; while one is shown it asks
     * nothing and only lets the clock take it off the glass once its time is up.
     * The interval is {@see HowOftenAScreenLooks}'s constant.
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

    /** Read the code held against the clock, or ask after the one being made. */
    private function followed(): HowThePairingCodeWent
    {
        $code = $this->code;

        return match (true) {
            $code instanceof APairingCode => $this->shown($code),
            $this->lapsed => new HowThePairingCodeReads()->expired(),
            $this->following === null => new HowThePairingCodeReads()->notAsked(),
            default => $this->askedAfter($this->following),
        };
    }

    /** Ask the stack what became of the code being made. */
    private function askedAfter(string $following): HowThePairingCodeWent
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): HowThePairingCodeWent => $this->answered($this->pairing->whatBecameOf($stack, $session, Job::named($following)), $stack),
            notHeld: static fn(): HowThePairingCodeWent => new HowThePairingCodeReads()->signedOut(),
        );
    }

    /** What the stack answered, as the screen draws it, holding the handle or the code. */
    private function answered(WhatBecameOfThePairingCode $became, Stack $stack): HowThePairingCodeWent
    {
        return $became->either(
            underway: function (Job $job): HowThePairingCodeWent {
                $this->following = $job->shown();

                return new HowThePairingCodeReads()->working();
            },
            made: function (APairingCode $code): HowThePairingCodeWent {
                $this->following = null;
                $this->code = $code;

                return $this->shown($code);
            },
            ended: function (): HowThePairingCodeWent {
                $this->following = null;

                return new HowThePairingCodeReads()->notAsked();
            },
            refused: function (string $because): HowThePairingCodeWent {
                $this->following = null;

                return new HowThePairingCodeReads()->refused($because);
            },
            met: function (Obstacle $why) use ($stack): HowThePairingCodeWent {
                $this->following = null;
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
            $this->lapsed = true;

            return new HowThePairingCodeReads()->expired();
        }

        return $this->drawn ??= new HowThePairingCodeReads()->made($code, $this->encoding->codeFor($code->line()), $this->here->zone());
    }
}
