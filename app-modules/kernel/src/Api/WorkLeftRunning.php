<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The handle of work this device started on a stack, kept so that leaving its screen does not lose it.
 *
 * A stack takes work on and answers a {@see Job} to follow it by, and the
 * screen that started it follows that handle while it is open. Leaving the
 * screen does not stop the work — the stack carries on with it — but it does
 * drop the screen, and with it the only copy of the handle. This keeps one, per
 * stack and per {@see KindOfWork}, so the screen opened again asks after the
 * same work and shows where it got to rather than offering to start it over.
 *
 * **Kept on the device because the stack cannot be asked.** Its contract
 * answers a handle when work starts and an outcome when asked by one; it has no
 * question for *which work did this device start*. So the handle is kept where
 * the device keeps what it knows between screens.
 *
 * **A handle is safe to keep.** {@see Job} says why: the action was delivered
 * and the stack named it, so asking after the name is a read, and repeating a
 * read changes nothing. Nothing kept here is ever replayed.
 *
 * **One per stack and kind, and the latest replaces the last.** Two stacks never
 * share a handle, and a screen that starts new work lets go of the old.
 *
 * **Not tied to the session.** Letting go of a refused session leaves this
 * alone: the work goes on whoever is signed in, and signing in again finds it.
 *
 * Every answer is {@see WhatAReturnFinds} and none raises: a device that cannot
 * keep a handle has lost the way back to the work and not the work, and a
 * screen says that rather than failing.
 *
 * **It is let go of with everything else kept.** A handle is a marker the
 * phone keeps about a stack, so clearing what the phone keeps clears it; the
 * work itself is on the stack and runs on.
 */
interface WorkLeftRunning extends ForgetsAStack, ForgetsEverythingKept
{
    /** What a screen for this kind of work on this stack finds on opening. */
    public function whatWasLeft(StackId $stack, KindOfWork $work): WhatAReturnFinds;

    /**
     * Keep the handle of work just started, in place of any kept before it.
     *
     * Answers what a return will find now: the handle where it was written
     * down, and nothing where the device would not keep it — which the screen
     * that started the work says while it is still open, because it is the last
     * moment anybody can be told.
     */
    public function remember(StackId $stack, KindOfWork $work, Job $job): WhatAReturnFinds;

    /**
     * Let go of the handle kept for this kind of work on this stack.
     *
     * Always answers nothing, because that is what a return will find. A store
     * that refuses to forget is one that will not open or is not there, and
     * while it refuses it reads no handle back either.
     */
    public function forget(StackId $stack, KindOfWork $work): WhatAReturnFinds;
}
