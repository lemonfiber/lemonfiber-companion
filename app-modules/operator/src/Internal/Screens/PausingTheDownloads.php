<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\Screens;

use function is_string;

use Modules\Connection\Api\LetsGoOfARefusedSession;
use Modules\Kernel\Api\Concealed;
use Modules\Kernel\Api\HowOftenAScreenLooks;
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
use Modules\Operator\Internal\AwaitsAnOutcome;
use Modules\Operator\Internal\OffersTheAppsSettings;
use Modules\Operator\Internal\Presenters\HowPausingReads;
use Modules\Operator\Internal\ViewModels\HowPausingWent;
use Modules\Operator\Internal\ViewModels\WhatPausingSays;
use Modules\Operator\Internal\ViewModels\WhatPausingWouldShow;
use Modules\Wayfinding\Api\Screens\AsksTheStackAgain;
use Modules\Wayfinding\Api\Screens\DrawsItsTemplate;
use Modules\Wayfinding\Api\TheWayAround;
use Modules\Wayfinding\Api\WhatItListensWith;
use Native\Mobile\Attributes\Lazy;
use Native\Mobile\Attributes\Poll;
use Native\Mobile\Edge\NativeComponent;

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
    use FindsItsWayAround;
    use DrawsItsTemplate;

    public const string TEMPLATE = 'operator::pausing-the-downloads';

    /**
     * What the rehearsal came to, once the frame has asked.
     *
     * `public` for {@see HowCurrentThisStackIs::$answered}'s reason.
     */
    public ?WhatPausingWouldShow $answered = null;

    /** The handle the rehearsal answered. Not shown and never kept past this screen. */
    public ?string $asking = null;

    /** The rehearsal's report, while there is one to agree to, held as the value because a yes can only quote the offer itself. */
    public ?WhatPausingComesTo $offered = null;

    /** Whether the operator has agreed, which is which of the two readings a frame draws. */
    public bool $agreed = false;

    /** The handle agreeing answered. Not shown and never kept past this screen. */
    public ?string $handle = null;

    /** What became of the yes, once this frame has asked. */
    public ?HowPausingWent $carriedOut = null;

    public function __construct(
        private readonly PausingDownloads $pausing,
        private readonly SecureStorage $storage,
        protected readonly TheWayAround $around,
        protected readonly TheAppsSettings $settings,
        protected readonly WhatItListensWith $listening,
    ) {}

    /** The same question this screen's cadence asks, answered from what it last heard. */
    public function awaitsAnOutcome(): bool
    {
        return $this->isWorking();
    }

    /** Which of pausing and resuming this screen is about, by the word the route carries, or none. */
    public function whatWasAsked(): ?WhatToDoWithTheDownloads
    {
        $named = $this->param('service');

        return is_string($named) ? WhatToDoWithTheDownloads::tryFrom($named) : null;
    }

    /** The lines that differ between pausing and resuming; pausing's where the route names neither, which draws none of them. */
    public function says(): WhatPausingSays
    {
        return new HowPausingReads()->lines($this->whatWasAsked() ?? WhatToDoWithTheDownloads::Pause);
    }

    /** What the rehearsal came to, asked once per frame. */
    public function answer(): WhatPausingWouldShow
    {
        return $this->answered ??= $this->ask();
    }

    /** Whether the operator has agreed to the rehearsal. */
    public function wasAgreedTo(): bool
    {
        return $this->agreed;
    }

    /** What became of the yes, asked once per frame. */
    public function done(): HowPausingWent
    {
        return $this->carriedOut ??= $this->followed();
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
        $this->handle = null;

        $this->carriedOut = $this->storage->resume($stack->id())->either(
            held: fn(Session $session): HowPausingWent => $this->pausing->carryOut($stack, $session, $offer)->either(
                started: function (Job $job): HowPausingWent {
                    $this->handle = $job->shown();

                    return new HowPausingReads()->running();
                },
                met: $this->lettingGoIfRefused($stack, new HowPausingReads()->met(...)),
            ),
            notHeld: static fn(): HowPausingWent => new HowPausingReads()->signedOut(),
        );
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
        $this->carriedOut = null;

        if ($this->agreed && is_string($this->handle)) {
            return;
        }

        if ($this->answered?->stands->isWorking() !== true) {
            $this->asking = null;
        }

        $this->agreed = false;
        $this->offered = null;
        $this->answered = null;
    }

    /**
     * Read again while the stack is rehearsing or carrying it out.
     *
     * It does nothing unless one of those is running, and it only ever reads:
     * the yes is sent by {@see agree()} and nothing else. The interval is
     * {@see HowOftenAScreenLooks}'s constant.
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

    /** Whether the stack is rehearsing, or carrying it out, right now. */
    public function isWorking(): bool
    {
        return $this->agreed ? $this->done()->stands->isWorking() : $this->answer()->stands->isWorking();
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

    /**
     * Read the rehearsal's handle, rehearsing first where there is none.
     *
     * A handle already in hand is read, and only the absence of one asks the
     * stack anything, so reading again is never a second piece of work on
     * somebody's machine.
     */
    private function read(Stack $stack, Session $session, WhatToDoWithTheDownloads $asked): WhatPausingWouldShow
    {
        $held = $this->asking;

        if (is_string($held)) {
            return $this->offer($stack, $session, Job::named($held));
        }

        return $this->pausing->rehearse($stack, $session, $asked)->either(
            started: function (Job $job) use ($stack, $session): WhatPausingWouldShow {
                $this->asking = $job->shown();

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
            ended: static fn(): WhatPausingWouldShow => new HowPausingReads()->offerEnded(),
            met: $this->lettingGoIfRefused($stack, new HowPausingReads()->notOffered(...)),
        );
    }

    /** Ask the stack what became of the yes. */
    private function followed(): HowPausingWent
    {
        $held = $this->handle;

        if (! is_string($held)) {
            return new HowPausingReads()->ended();
        }

        $stack = $this->stack();

        return $this->storage->resume($stack->id())->either(
            held: fn(Session $session): HowPausingWent => $this->pausing->whatBecameOf($stack, $session, Job::named($held))->either(
                stillRunning: static fn(): HowPausingWent => new HowPausingReads()->running(),
                answered: static fn(WhatPausingComesTo $report): HowPausingWent => new HowPausingReads()->done($report),
                ended: static fn(): HowPausingWent => new HowPausingReads()->ended(),
                met: $this->lettingGoIfRefused($stack, new HowPausingReads()->met(...)),
            ),
            notHeld: static fn(): HowPausingWent => new HowPausingReads()->signedOut(),
        );
    }
}
