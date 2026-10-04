<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use Illuminate\View\View;

use function is_string;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\ADownloadHeld;
use Modules\Kernel\Api\ADownloadLetGo;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\ItsContent;
use Modules\Kernel\Api\Job;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\StoppingSeeding;
use Modules\Kernel\Api\TheAppsSettings;
use Modules\Kernel\Api\WhatItShowsDoes;
use Modules\Kernel\Api\WhatLettingItGoCosts;
use Modules\News\Api\Noticing;
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowLettingItGoReads;
use Modules\Operator\Internal\ViewModels\HowLettingItGoWent;
use Modules\Operator\Internal\ViewModels\WhatLettingItGoWouldShow;
use Modules\Operator\Internal\WhereAStackIs;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

use function trim;
use function view;

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
    use OffersTheAppsSettings;
    use LetsGoOfARefusedSession;
    use FindsItsWayAround;

    /**
     * What the offer came to, once the frame has asked.
     *
     * `public` for {@see HowCurrentThisStackIs::$answered}'s reason.
     */
    public ?WhatLettingItGoWouldShow $answered = null;

    /** The handle asking what it would cost answered. Not shown and never kept past this screen. */
    public ?string $asking = null;

    /**
     * The offer, while there is one to agree to.
     *
     * Held as the value rather than as the fold, because a yes can only be
     * sent against the offer itself.
     */
    public ?WhatLettingItGoCosts $offered = null;

    /** Whether the operator has agreed, which is which of the two readings a frame draws. */
    public bool $agreed = false;

    /** The handle agreeing answered. Not shown and never kept past this screen. */
    public ?string $handle = null;

    /** What became of the yes, once this frame has asked. */
    public ?HowLettingItGoWent $carriedOut = null;

    public function __construct(
        private readonly StoppingSeeding $stopping,
        private readonly SecureStorage $storage,
        private readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
        protected readonly Noticing $noticing,
    ) {}

    /**
     * The stack this screen is about.
     *
     * Read from the route on every frame, for
     * {@see WhatThisMachineKeepsHere::stack()}'s reason.
     */
    public function stack(): Stack
    {
        return $this->around->stackOn($this);
    }

    /** Where this machine's screens are. */
    public function goes(): WhereAStackIs
    {
        return WhereAStackIs::of($this->stack()->id());
    }

    /** The same question this screen's cadence asks, answered from what it last heard. */
    public function awaitsAnOutcome(): bool
    {
        return $this->isWorking();
    }

    public function render(): View
    {
        return view('operator::letting-a-download-go');
    }

    /** The download this screen is about, by the name the route carries. */
    public function downloadNamed(): string
    {
        $named = $this->param('service');

        return is_string($named) ? $named : '';
    }

    /** What stopping seeding would cost, asked once per frame. */
    public function answer(): WhatLettingItGoWouldShow
    {
        return $this->answered ??= $this->ask();
    }

    /** Whether the operator has agreed to the offer. */
    public function wasAgreedTo(): bool
    {
        return $this->agreed;
    }

    /** What became of the yes, asked once per frame. */
    public function done(): HowLettingItGoWent
    {
        return $this->carriedOut ??= $this->followed();
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
        $this->handle = null;

        $this->carriedOut = $this->storage->resume($stack->id())->either(
            held: fn(Session $session): HowLettingItGoWent => $this->stopping->stop($stack, $session, $offer)->either(
                started: function (Job $job): HowLettingItGoWent {
                    $this->handle = $job->shown();

                    return new HowLettingItGoReads()->running();
                },
                met: fn(Obstacle $why): HowLettingItGoWent => $this->refused($why, $stack),
            ),
            notHeld: static fn(): HowLettingItGoWent => new HowLettingItGoReads()->signedOut(),
        );
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
        $this->carriedOut = null;

        if ($this->agreed && is_string($this->handle)) {
            return;
        }

        if ($this->answered?->isWorking !== true) {
            $this->asking = null;
        }

        $this->agreed = false;
        $this->offered = null;
        $this->answered = null;
    }

    /**
     * Read again while the stack is working something out or carrying it out.
     *
     * It does nothing unless one of those is running, so an offer on the
     * screen and a finished report are not read over and over, and it only
     * ever reads: the yes is sent by {@see agree()} and nothing else. The
     * interval is {@see HowOftenAScreenLooks}'s constant.
     */
    #[Poll(HowOftenAScreenLooks::WHILE_WORK_RUNS_MS)]
    public function whileItRuns(): void
    {
        if (! $this->isWorking()) {
            return;
        }

        if ($this->agreed) {
            $this->carriedOut = null;

            return;
        }

        $this->answered = null;
    }

    /** Whether the stack is working out the offer, or stopping the seeding, right now. */
    public function isWorking(): bool
    {
        return $this->agreed ? $this->done()->isWorking : $this->answer()->isWorking;
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

    /**
     * Read the asking's handle, asking first where there is none.
     *
     * A handle already in hand is read, and only the absence of one asks the
     * stack anything, so reading again is never a second piece of work on
     * somebody's machine.
     */
    private function read(Stack $stack, Session $session, ADownloadHeld $download): WhatLettingItGoWouldShow
    {
        $held = $this->asking;

        if (is_string($held)) {
            return $this->offer($stack, $session, Job::named($held));
        }

        return $this->stopping->whatItWouldCost($stack, $session, $download)->either(
            started: function (Job $job) use ($stack, $session): WhatLettingItGoWouldShow {
                $this->asking = $job->shown();

                return $this->offer($stack, $session, $job);
            },
            met: fn(Obstacle $why): WhatLettingItGoWouldShow => $this->notOffered($why, $stack),
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
            ended: static fn(): WhatLettingItGoWouldShow => new HowLettingItGoReads()->offerEnded(),
            met: fn(Obstacle $why): WhatLettingItGoWouldShow => $this->notOffered($why, $stack),
        );
    }

    /** Ask the stack what became of the yes. */
    private function followed(): HowLettingItGoWent
    {
        $held = $this->handle;

        if (! is_string($held)) {
            return new HowLettingItGoReads()->ended();
        }

        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): HowLettingItGoWent => $this->stopping->whatBecameOf($stack, $session, Job::named($held))->either(
                stillRunning: static fn(): HowLettingItGoWent => new HowLettingItGoReads()->running(),
                done: static fn(ADownloadLetGo $report): HowLettingItGoWent => new HowLettingItGoReads()->done($report),
                ended: static fn(): HowLettingItGoWent => new HowLettingItGoReads()->ended(),
                met: fn(Obstacle $why): HowLettingItGoWent => $this->refused($why, $stack),
            ),
            notHeld: static fn(): HowLettingItGoWent => new HowLettingItGoReads()->signedOut(),
        );
    }

    /** What the operator met asking for the offer, letting go of a session the stack refused. */
    private function notOffered(Obstacle $why, Stack $stack): WhatLettingItGoWouldShow
    {
        $this->letGoOfTheSession($why, $stack);

        return new HowLettingItGoReads()->notOffered($why);
    }

    /** What the operator met after the yes, letting go of a session the stack refused. */
    private function refused(Obstacle $why, Stack $stack): HowLettingItGoWent
    {
        $this->letGoOfTheSession($why, $stack);

        return new HowLettingItGoReads()->met($why);
    }
}
