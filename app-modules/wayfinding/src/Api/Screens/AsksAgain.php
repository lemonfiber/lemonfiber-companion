<?php

declare(strict_types=1);

namespace Modules\Wayfinding\Api\Screens;

use Native\Mobile\Edge\NativeComponent;

/**
 * Ask the stack again, by letting go of what the screen was answered.
 *
 * The screen holds its answer in `$answered`, which is the coupling, stated
 * here because a trait cannot declare it. Forgetting the answer rather than
 * asking and comparing means the next accessor asks, so there is one path to
 * an answer and it is the one every frame takes; the session is resumed again
 * with it, so one that ended underneath the screen is not reused.
 *
 * **It is the action an obstacle must not take away.** An obstacle screen with
 * nothing on it leaves no way back but leaving and returning, and an operator
 * who has just changed something at the machine wants it on a screen that
 * answered too.
 *
 * @phpstan-require-extends NativeComponent
 */
trait AsksAgain
{
    public function again(): void
    {
        $this->answered = null;
    }
}
