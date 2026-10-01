<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\SecureStorage;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\Supervising;
use Modules\Operator\Internal\Presenters\HowAListingReads;
use Modules\Operator\Internal\ViewModels\TheFormsAsFound;
use Modules\Operator\Internal\ViewModels\WhatThisStackRunsTurnedOutToBe;
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
 * @phpstan-require-extends NativeComponent
 */
trait TakesItsFormsAFrameLater
{
    use AsksWhatTheStackIsRunning;
    use AsksWhatFormsItHas;
    use ReadsAStackOnceAFrame;

    /** Ask what is running again, and the forms where they could not be read. */
    public function again(): void
    {
        $this->answered = null;
        $this->formsAgain();
    }

    /** What is running, read where it is due, or what stopped the forms being read. */
    private function listingOf(Stack $stack, SecureStorage $storage, Supervising $supervising): WhatThisStackRunsTurnedOutToBe
    {
        if (! $this->answered instanceof WhatThisStackRunsTurnedOutToBe) {
            $this->readsItsStack();
            $this->answered = $this->askWhatIsRunning($stack, $storage, $supervising);
        }

        $forms = $this->formsFound;

        if (! $forms instanceof TheFormsAsFound || $forms->went->cameBack()) {
            return $this->answered;
        }

        return new HowAListingReads()->stoppedBy($forms->went);
    }

    /** The forms, or nothing while they wait for a frame of their own. */
    private function formsOf(Stack $stack, SecureStorage $storage, Supervising $supervising): ?TheFormsAsFound
    {
        if ($this->formsFound instanceof TheFormsAsFound || ! $this->mayReadItsStack()) {
            return $this->formsFound;
        }

        $this->formsFound = $this->askWhatFormsItHas($stack, $storage, $supervising);

        // Forms that could not be read are said on the next frame, as the
        // obstacle the whole screen met.
        $this->waitsThisFrame = ! $this->formsFound->went->cameBack();

        return $this->formsFound;
    }
}
