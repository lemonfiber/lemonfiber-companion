<?php

declare(strict_types=1);

namespace Modules\Connection\Api;

use Modules\Kernel\Api\ForgetsAStack;
use Modules\Kernel\Api\RemovalsUnderWay;
use Modules\Kernel\Api\StackId;
use Modules\Kernel\Api\StacksBeingRemoved;

/**
 * Remove from phone: the stack's pairing and session, and every reading,
 * setting and marker kept for it, as one act.
 *
 * **Written down before anything goes.** The removal is recorded first, and a
 * record that cannot be written refuses the removal with nothing touched. Then
 * every keeper lets go of the stack, and the record is struck off only once
 * none keeps anything of it. A removal the app was stopped in the middle of,
 * or one a keeper could not finish, is still recorded, and
 * {@see finishWhatWasLeft()} finishes it as the app opens: nothing
 * half-removed is left behind.
 *
 * The stack itself keeps running; nothing here reaches it.
 */
final readonly class RemovingAStack
{
    public function __construct(private RemovalsUnderWay $underWay, private ForgetsAStack $keepers) {}

    public function remove(StackId $stack): WhatBecameOfRemoving
    {
        if (! $this->underWay->begin($stack)) {
            return WhatBecameOfRemoving::Refused;
        }

        return $this->finish($stack);
    }

    /** Finish every removal that began and was not finished, as the app opens, and answer those still not. */
    public function finishWhatWasLeft(): StacksBeingRemoved
    {
        $left = StacksBeingRemoved::none();

        foreach ($this->underWay->underWay() as $stack) {
            if ($this->finish($stack) !== WhatBecameOfRemoving::Removed) {
                $left = $left->with($stack);
            }
        }

        return $left;
    }

    private function finish(StackId $stack): WhatBecameOfRemoving
    {
        $this->keepers->forgetTheStack($stack);

        if ($this->keepers->keepsAnythingOf($stack) || ! $this->underWay->finished($stack)) {
            return WhatBecameOfRemoving::Finishing;
        }

        return WhatBecameOfRemoving::Removed;
    }
}
