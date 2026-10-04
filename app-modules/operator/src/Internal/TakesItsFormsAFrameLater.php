<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\Daemons;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\Obstacle;
use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Supervising;
use Modules\Operator\Internal\Presenters\HowAListingReads;
use Modules\Operator\Internal\ViewModels\HowTheReadingWent;
use Modules\Operator\Internal\ViewModels\TheFormsAsFound;
use Modules\Operator\Internal\ViewModels\WhatThisStackRunsTurnedOutToBe;
use Modules\Services\Api\KeepingWhatItRuns;
use Native\Mobile\Edge\NativeComponent;

/**
 * A screen about what a stack runs that also needs the forms it declares.
 *
 * What is running is the screen's own reading and is taken on every frame it
 * is due. The forms are a second reading, so they are taken on the frame after
 * the first and then held, while what is running is read again on its own.
 *
 * Forms that could not be read stop the screen as the listing would have: the
 * frame that found out asks for the next one, and that one draws the obstacle.
 *
 * **What is running is kept, and stands until a fresh listing arrives.** Each
 * fresh listing is handed to `services` to keep. A listing that does not come
 * back is drawn beside the one the phone kept, with its age and every action
 * on it waiting, and the forms are then the ones the kept listing names, since
 * asking for them would meet what the listing met.
 *
 * @phpstan-require-extends NativeComponent
 */
trait TakesItsFormsAFrameLater
{
    use AsksWhatTheStackIsRunning;
    use AsksWhatFormsItHas;
    use ReadsAStackOnceAFrame;

    /**
     * The forms the kept listing names, which stand in for the forms while it is drawn.
     *
     * `protected` for {@see AsksWhatTheStackIsRunning::$answered}'s reason.
     */
    protected ?TheFormsAsFound $formsKept = null;

    /** Ask what is running again, and the forms where they could not be read. */
    public function again(): void
    {
        $this->answered = null;
        $this->formsAgain();
    }

    /** What is running, read where it is due, or what stopped the forms being read. */
    private function listingOf(Stack $stack, SecureStorage $storage, Supervising $supervising, KeepingWhatItRuns $kept): WhatThisStackRunsTurnedOutToBe
    {
        if (! $this->answered instanceof WhatThisStackRunsTurnedOutToBe) {
            $this->readsItsStack();
            $this->answered = $this->askAndKeep($stack, $storage, $supervising, $kept);
        }

        $forms = $this->formsFound;

        if (! $forms instanceof TheFormsAsFound || $forms->went->cameBack()) {
            return $this->answered;
        }

        return new HowAListingReads()->stoppedBy($forms->went);
    }

    /** The forms, or nothing while they wait for a frame of their own; for a kept listing, the forms it names. */
    private function formsOf(Stack $stack, SecureStorage $storage, Supervising $supervising, KeepingWhatItRuns $kept): ?TheFormsAsFound
    {
        if ($this->listingOf($stack, $storage, $supervising, $kept)->waitsForTheStack) {
            return $this->formsKept;
        }

        if ($this->formsFound instanceof TheFormsAsFound || ! $this->mayReadItsStack()) {
            return $this->formsFound;
        }

        $this->formsFound = $this->askWhatFormsItHas($stack, $storage, $supervising);

        // Forms that could not be read are said on the next frame, as the
        // obstacle the whole screen met.
        $this->waitsThisFrame = ! $this->formsFound->went->cameBack();

        return $this->formsFound;
    }

    /** The listing the phone kept, drawn on the screen's first frame before the stack is asked; true where there is one. */
    private function opensOnWhatWasKept(Stack $stack, KeepingWhatItRuns $kept): bool
    {
        $opening = $this->besideWhatWasKept($stack, $kept, HowTheReadingWent::itCameBack(), new HowAListingReads()->signedOut());

        if (! $opening->waitsForTheStack) {
            return false;
        }

        $this->answered = $opening;

        return true;
    }

    /** Resume the session, ask the stack, keep what came back, and stand on what was kept where nothing did. */
    private function askAndKeep(Stack $stack, SecureStorage $storage, Supervising $supervising, KeepingWhatItRuns $kept): WhatThisStackRunsTurnedOutToBe
    {
        return $storage->resume($stack->id())->either(
            held: fn(Session $session): WhatThisStackRunsTurnedOutToBe => $supervising->running($stack, $session)->either(
                these: static function (Daemons $daemons) use ($stack, $kept): WhatThisStackRunsTurnedOutToBe {
                    $kept->keep($stack->id(), $daemons);

                    return new HowAListingReads()->these($daemons);
                },
                met: function (Obstacle $why) use ($stack, $kept): WhatThisStackRunsTurnedOutToBe {
                    $this->letGoOfTheSession($why, $stack);

                    return $this->besideWhatWasKept($stack, $kept, HowTheReadingWent::somethingStopped($why), new HowAListingReads()->met($why));
                },
            ),
            notHeld: fn(): WhatThisStackRunsTurnedOutToBe => $this->besideWhatWasKept($stack, $kept, HowTheReadingWent::theSessionEnded(), new HowAListingReads()->signedOut()),
        );
    }

    /**
     * The listing the phone kept, beside what this frame's asking met, or what it met alone where nothing was kept.
     *
     * The forms the kept listing names stand in for the forms until a fresh
     * listing arrives, so a form it names is offered, its actions waiting.
     */
    private function besideWhatWasKept(Stack $stack, KeepingWhatItRuns $kept, HowTheReadingWent $met, WhatThisStackRunsTurnedOutToBe $unread): WhatThisStackRunsTurnedOutToBe
    {
        return $kept->lastKept($stack->id())->either(
            kept: function (Daemons $daemons, Instant $readAt, Instant $now) use ($met): WhatThisStackRunsTurnedOutToBe {
                $this->formsKept = new HowAListingReads()->formsNamedIn($daemons);

                return new HowAListingReads()->kept($daemons, $readAt, $now, $met);
            },
            nothing: static fn(): WhatThisStackRunsTurnedOutToBe => $unread,
        );
    }
}
