<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Closure;
use Modules\Health\Api\WhatAStreamSaidSoFar;
use Modules\Kernel\Api\HowOftenAScreenLooks;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatAStreamSaid;
use Modules\Kernel\Api\WhatIsBeingCopied;
use Modules\Kernel\Api\Whose;
use Modules\Operator\Internal\Presenters\HowTheCopyRunningReads;
use Modules\Operator\Internal\ViewModels\ACopyRunningAsShown;
use Modules\Wayfinding\Api\Screens\ReadsAStackOnceAFrame;
use Native\Mobile\Attributes\Poll;

/**
 * The backup or restore running on the screen's stack: read once when the screen opens, then followed on the stack's stream.
 *
 * A screen opened mid-copy reads the copy as soon as a frame is free of its own
 * reading, rather than waiting for the stream's next word, and a copy started
 * anywhere else, by anybody, is the one shown. Only the operator's session asks: on a member's, or with none, nothing
 * is asked and nothing is drawn.
 *
 * A screen that shows less than the whole copy hears it on the stream alone,
 * through {@see theCopyTheStreamSaysRuns()}: nothing is read for it, and only a
 * copy the stream says runs is drawn, since the stream says the copy as a
 * client connects.
 *
 * The screen takes `$storage` and `$listening`, whose `copying` hears the copy,
 * begins each frame with `aFrameBegins()`, says `readsItsStack()` on a frame it
 * reads its own answer, and lets go of the copy through `letGoOfTheCopy()` when
 * it lets go of what else it hears.
 */
trait FollowsTheCopyRunning
{
    use ReadsAStackOnceAFrame;

    /**
     * What the screen has heard of the copy so far, kept between frames for {@see OffersTheAppsSettings}'s reason.
     *
     * @var WhatAStreamSaidSoFar<WhatIsBeingCopied>|null
     */
    public ?WhatAStreamSaidSoFar $copyHeard = null;

    /** Whether the copy is the session's to see: false on a member's session and with none. */
    public bool $copyIsShown = true;

    /** Hear what the stream says of the copy, while the app is in front. */
    #[Poll(HowOftenAScreenLooks::WHILE_LISTENING_MS)]
    public function followTheCopy(): void
    {
        $held = $this->copyHeard;
        $now = $this->listening->clock->now();

        if (! $held instanceof WhatAStreamSaidSoFar) {
            return;
        }

        if (! $this->listening->capture->isInFront()) {
            $this->copyHeard = $held->after($this->listening->copying->letGo(), $now)->wentAway();

            return;
        }

        if (! $this->copyIsShown || ! $held->mayListen($now)) {
            return;
        }

        $held = $held->after($this->copyFromTheStream(), $now);
        $this->copyHeard = $held->hasGoneQuiet($now) ? $held->after($this->listening->copying->letGo(), $now) : $held;
    }

    /** The copy running, as the stack's reading says it, or nothing where it is not the session's to see or waits for a frame of its own. */
    public function theCopyRunning(): ?ACopyRunningAsShown
    {
        if (! $this->copyHeard instanceof WhatAStreamSaidSoFar && ! $this->mayReadItsStack()) {
            return null;
        }

        $heard = $this->copyHeardSoFar();

        return $this->copyIsShown ? new HowTheCopyRunningReads()->of($heard, $this->listening->clock->now()) : null;
    }

    /** The copy running, where the stream says one runs, heard without reading it; nothing where none runs or the app cannot tell. */
    public function theCopyTheStreamSaysRuns(): ?ACopyRunningAsShown
    {
        $this->copyHeard ??= WhatAStreamSaidSoFar::nothingYet();
        $copy = $this->theCopyRunning();

        return $copy?->written === null ? null : $copy;
    }

    /** Stop hearing the copy, keeping what was heard as no longer current. */
    protected function letGoOfTheCopy(): void
    {
        $said = $this->listening->copying->letGo();
        $this->copyHeard = $this->copyHeard?->after($said, $this->listening->clock->now())->wentAway();
    }

    /** @return WhatAStreamSaidSoFar<WhatIsBeingCopied> */
    private function copyHeardSoFar(): WhatAStreamSaidSoFar
    {
        if ($this->copyHeard instanceof WhatAStreamSaidSoFar) {
            return $this->copyHeard;
        }

        $this->copyIsShown = true;
        $read = $this->asTheOperatorAboutTheCopy(fn(Stack $stack, Session $session): WhatAStreamSaid => $this->listening->copying->now($stack, $session)->either(
            said: static fn(WhatIsBeingCopied $copying): WhatAStreamSaid => WhatAStreamSaid::said($copying),
            refused: function (Obstacle $why) use ($stack): WhatAStreamSaid {
                $this->letGoOfTheSession($why, $stack);

                return WhatAStreamSaid::met($why);
            },
        ));

        return $this->copyHeard = WhatAStreamSaidSoFar::nothingYet()->after($read, $this->listening->clock->now());
    }

    /** @return WhatAStreamSaid<WhatIsBeingCopied> */
    private function copyFromTheStream(): WhatAStreamSaid
    {
        return $this->asTheOperatorAboutTheCopy(function (Stack $stack, Session $session): WhatAStreamSaid {
            $said = $this->listening->copying->heard($stack, $session);
            $letGoIfRefused = function (Obstacle $why) use ($said, $stack): WhatAStreamSaid {
                $this->letGoOfTheSession($why, $stack);

                return $said;
            };
            $asSaid = static fn(): WhatAStreamSaid => $said;

            return $said->either(nothing: $asSaid, alive: $asSaid, said: $asSaid, closed: $asSaid, met: $letGoIfRefused);
        });
    }

    /**
     * @param Closure(Stack, Session): WhatAStreamSaid<WhatIsBeingCopied> $asking
     *
     * @return WhatAStreamSaid<WhatIsBeingCopied>
     */
    private function asTheOperatorAboutTheCopy(Closure $asking): WhatAStreamSaid
    {
        $stack = $this->stack();
        $resumed = $this->storage->resume($stack->id());

        return $resumed->either(
            held: fn(Session $session): WhatAStreamSaid => $resumed->whoseItIs(
                nobody: fn(): WhatAStreamSaid => $this->notShown(),
                theirs: fn(Whose $whose): WhatAStreamSaid => $whose->either(
                    operator: static fn(): WhatAStreamSaid => $asking($stack, $session),
                    member: fn(): WhatAStreamSaid => $this->notShown(),
                ),
            ),
            notHeld: fn(): WhatAStreamSaid => $this->notShown(),
        );
    }

    /** @return WhatAStreamSaid<never> */
    private function notShown(): WhatAStreamSaid
    {
        $this->copyIsShown = false;

        return WhatAStreamSaid::closed();
    }
}
