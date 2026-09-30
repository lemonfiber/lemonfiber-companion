<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Closure;
use Illuminate\View\View;

use function is_string;

use Modules\Kernel\Api\AnUninstall;
use Modules\Kernel\Api\AnUninstallAgreed;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HowOften;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\TakingLemonfiberOff;
use Modules\Kernel\Api\WhatBecameOfTheUninstall;
use Modules\Kernel\Api\WhetherToWait;
use Modules\Kernel\Api\WhichRemoval;
use Modules\Operator\Internal\LetsGoOfARefusedSession;
use Modules\Operator\Internal\Presenters\HowTakingItOffReads;
use Modules\Operator\Internal\TheWayAround;
use Modules\Operator\Internal\ViewModels\TakingItOffTurnedOutToBe;
use Modules\Operator\Internal\WhereAStackIs;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

use function view;

/**
 * Taking lemonfiber off this machine: one removal chosen, read, agreed to on its own, and followed.
 *
 * **Four removals, each its own decision.** It opens on reading the first,
 * stopping everything, which removes nothing, and each of the others is read
 * only once it is chosen: what it takes and leaves, every line it reaches with
 * what is kept and why, what is not lemonfiber's, what is still coming down,
 * and what lemonfiber cannot take, with how much of it could be read said
 * before anything else. Reading is never agreeing: nothing goes without the
 * yes beneath the reading.
 *
 * **The yes is given here, against the reading on the screen.** It quotes
 * the reading by its name, and nothing is carried in from another screen or
 * from another removal: choosing another removal lets go of the reading, and
 * the volume acknowledged with it. Where downloads are still coming down, the
 * yes is two, waiting and going ahead, and neither is chosen for the
 * operator.
 *
 * **Removing configuration takes what admits this app.** That is said before
 * the yes; after it, an answer that cannot be read is said as the way in
 * having gone, never as a credential refused, and the pairing stays.
 *
 * `Concealed` for the reason every stack-facing screen here is.
 */
#[Lazy]
#[Concealed]
final class TakingItOffThisMachine extends NativeComponent
{
    use LetsGoOfARefusedSession;

    /** The removal being read, as its own word, or empty while the four are in front of the operator to choose from. */
    public string $tier = WhichRemoval::Stop->value;

    /** The reading, while it is in front of the operator to be agreed to. */
    public ?AnUninstall $surveyed = null;

    /**
     * Whether the operator has acknowledged, apart from the yes, that the data is on a network share or a drive that unplugs.
     *
     * Public for {@see HowCurrentThisStackIs::$answered}'s reason.
     */
    public bool $volumeAcknowledged = false;

    /** The handle of the removal being followed, while there is one. Not shown and never kept past this screen. */
    public ?string $following = null;

    /** The removal a yes was given to, as its own word, or empty before one was. */
    public string $agreedTo = '';

    /** Where it has got to, once this frame has asked. Public for {@see WhatIsRunningHere::$answered}'s reason. */
    public ?TakingItOffTurnedOutToBe $going = null;

    public function __construct(
        private readonly TakingLemonfiberOff $takingItOff,
        private readonly SecureStorage $storage,
        private readonly TheWayAround $around,
    ) {}

    /** The stack this screen is about, read from the route on every frame. */
    public function stack(): Stack
    {
        return $this->around->stackNamed($this->param('stack'));
    }

    /** Where this machine's screens are. */
    public function goes(): WhereAStackIs
    {
        return WhereAStackIs::of($this->stack()->id());
    }

    public function render(): View
    {
        return view('operator::taking-it-off-this-machine');
    }

    /**
     * Where taking it off has got to, asked once per frame.
     *
     * Asks after the removal being followed where there is one, reads the
     * removal chosen where there is one, and otherwise asks nothing.
     */
    public function answer(): TakingItOffTurnedOutToBe
    {
        return $this->going ??= $this->asked();
    }

    /**
     * Read one removal: `stop`, `services`, `configuration` or `media`.
     *
     * Lets go of any reading and acknowledgement held for another, so a yes
     * is never carried from one removal to the next. A word naming none of
     * the four, or a removal already being followed, changes nothing.
     */
    public function choose(string $word): void
    {
        $tier = WhichRemoval::tryFrom($word);

        if (! $tier instanceof WhichRemoval || is_string($this->following)) {
            return;
        }

        $this->tier = $tier->value;
        $this->letGoOfTheReading();
    }

    /** Put the four removals back in front of the operator, letting go of the reading. */
    public function chooseAgain(): void
    {
        if (is_string($this->following)) {
            return;
        }

        $this->tier = '';
        $this->letGoOfTheReading();
    }

    /** Say, apart from the yes, that the data being on this volume is known. */
    public function acknowledgeTheVolume(): void
    {
        $this->volumeAcknowledged = true;
    }

    /**
     * Whether the reading on the screen names a volume the operator has not yet acknowledged.
     *
     * The yes waits on it: a removal across a mount somebody forgot was a
     * mount is otherwise found out afterwards.
     */
    public function stillToAcknowledge(): bool
    {
        $surveyed = $this->surveyed;

        return $surveyed instanceof AnUninstall && $surveyed->manifest()->volume() !== '' && ! $this->volumeAcknowledged;
    }

    /** Take it off now, interrupting anything still coming down. */
    public function goAhead(): void
    {
        $this->agree(WhetherToWait::GoAheadNow);
    }

    /** Let what is still coming down land, then take it off. */
    public function waitThenGo(): void
    {
        $this->agree(WhetherToWait::ForTheDownloads);
    }

    /**
     * Ask again, because the operator said so.
     *
     * After a yes the stack took on, it asks after the same handle. Otherwise
     * it reads the chosen removal afresh, which a yes has to be given to
     * again: a yes that met something on the way is never sent a second time
     * on its own.
     */
    public function again(): void
    {
        $this->going = null;

        if (is_string($this->following)) {
            return;
        }

        $this->letGoOfTheReading();
    }

    /**
     * Ask after the removal again while the stack is carrying it out.
     *
     * Nothing happens unless it is running. The interval is
     * {@see HowOften}'s.
     */
    #[Poll(HowOften::WHILE_WORK_RUNS_MS)]
    public function whileItRuns(): void
    {
        if ($this->answer()->isWorking) {
            $this->going = null;
        }
    }

    /**
     * Give the yes against the reading on the screen.
     *
     * Silent where no reading is held, where it is not a reading anybody can
     * agree to, or where a volume it named has not been acknowledged: there
     * is nothing to agree to yet.
     */
    private function agree(WhetherToWait $waiting): void
    {
        $surveyed = $this->surveyed;

        if (! $surveyed instanceof AnUninstall || ! $surveyed->removal()->isAReading()) {
            return;
        }

        if ($this->stillToAcknowledge()) {
            return;
        }

        $agreed = AnUninstallAgreed::after($surveyed, $waiting, acknowledgedTheVolume: $this->volumeAcknowledged);
        $this->surveyed = null;
        $this->agreedTo = $agreed->tier()->value;

        $this->going = $this->put(
            fn(Stack $stack, Session $session): WhatBecameOfTheUninstall => $this->takingItOff->takeItOff($stack, $session, $agreed),
        );
    }

    /** What the stack is asked this frame: after the removal being followed, or the removal chosen. */
    private function asked(): TakingItOffTurnedOutToBe
    {
        $following = $this->following;

        if (is_string($following)) {
            return $this->put(
                fn(Stack $stack, Session $session): WhatBecameOfTheUninstall => $this->takingItOff->whatBecameOf($stack, $session, Job::named($following)),
            );
        }

        $tier = WhichRemoval::tryFrom($this->tier);

        if (! $tier instanceof WhichRemoval) {
            return new HowTakingItOffReads()->notChosen();
        }

        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TakingItOffTurnedOutToBe => $this->takingItOff->surveyed($stack, $session, $tier)->either(
                found: function (AnUninstall $uninstall): TakingItOffTurnedOutToBe {
                    $this->surveyed = $uninstall->removal()->isAReading() ? $uninstall : null;

                    return new HowTakingItOffReads()->answered($uninstall, agreed: false);
                },
                met: function (Obstacle $why) use ($stack): TakingItOffTurnedOutToBe {
                    $this->letGoOfTheSession($why, $stack);

                    return new HowTakingItOffReads()->met($why);
                },
            ),
            notHeld: static fn(): TakingItOffTurnedOutToBe => new HowTakingItOffReads()->signedOut(),
        );
    }

    /**
     * The removal put to the stack, with the session this device holds for it.
     *
     * @param Closure(Stack, Session): WhatBecameOfTheUninstall $asking
     */
    private function put(Closure $asking): TakingItOffTurnedOutToBe
    {
        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): TakingItOffTurnedOutToBe => $this->shown($asking($stack, $session), $stack),
            notHeld: static fn(): TakingItOffTurnedOutToBe => new HowTakingItOffReads()->signedOut(),
        );
    }

    /** What the stack said about the removal, as the screen draws it, holding what to follow. */
    private function shown(WhatBecameOfTheUninstall $became, Stack $stack): TakingItOffTurnedOutToBe
    {
        $endsThisSession = WhichRemoval::tryFrom($this->agreedTo)?->takesWhatAdmitsThisApp() === true;

        return $became->either(
            underway: function (Job $job) use ($endsThisSession): TakingItOffTurnedOutToBe {
                $this->following = $job->shown();

                return new HowTakingItOffReads()->running($endsThisSession);
            },
            answered: function (AnUninstall $uninstall): TakingItOffTurnedOutToBe {
                $this->following = null;

                return new HowTakingItOffReads()->answered($uninstall, agreed: true);
            },
            ended: function () use ($endsThisSession): TakingItOffTurnedOutToBe {
                $this->following = null;

                return new HowTakingItOffReads()->ended($endsThisSession);
            },
            refused: function (string $because): TakingItOffTurnedOutToBe {
                $this->following = null;

                return new HowTakingItOffReads()->refused($because);
            },
            met: function (Obstacle $why) use ($stack, $endsThisSession): TakingItOffTurnedOutToBe {
                $this->letGoOfTheSession($why, $stack);

                return new HowTakingItOffReads()->unreadAfterTheYes($why, $endsThisSession);
            },
        );
    }

    /** Let go of the reading, the acknowledgement given with it, and any yes, so the next reading is agreed to afresh. */
    private function letGoOfTheReading(): void
    {
        $this->surveyed = null;
        $this->volumeAcknowledged = false;
        $this->agreedTo = '';
        $this->going = null;
    }
}
