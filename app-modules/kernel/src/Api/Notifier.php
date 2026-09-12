<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Where a notification goes to be seen.
 *
 * A port because there is no notification on a laptop. Behind it on a handset
 * is the platform's own notification centre; behind it in every test is a fake
 * that records, which is what lets the rest of the suite assert "the operator
 * was told" without a device.
 *
 * **Local, never pushed.** `N4-R11` says every notification originates in the
 * core's decisions, and `N4-R10` and `N4-R20` bound what one may carry — but the
 * reason this port exists at all, rather than a push subscription, is narrower
 * than any of them. A push notification's payload travels through Google's or
 * Apple's relay to reach the handset, which is a third party reading what a
 * stack said about somebody's home. {@see \Tests\Arch\NothingLeavesThisDeviceTest}
 * is the rule; a local notification is the implementation that keeps it, because
 * it never leaves the device it is displayed on.
 *
 * **`N4-R15` is the caller's, and deliberately so.** Whether a stack is still
 * configured is a fact about this device, not about the notification centre, and
 * an adapter asking it would need the whole list of stacks to display one alert.
 * {@see Notification::concernsOneOf()} is where that question is answered, and
 * {@see WhyNothingIsShown::TheStackIsGone} is how the answer is reported back
 * through the same type as every other refusal.
 */
interface Notifier
{
    /**
     * Whether the operator has allowed notifications at all.
     *
     * Asked separately from showing one so that a screen can offer to ask
     * (`N4-R13`) at a moment the operator is already thinking about it, rather
     * than the app discovering the refusal the first time it has something
     * worth saying and silently dropping it.
     */
    public function isPermitted(): bool;

    /**
     * Put a notification in front of the operator, or say why not.
     *
     * Answers {@see Shown} rather than raising: an operator who has not granted
     * permission is an ordinary state of the world, and a method that returns
     * nothing can report one only by throwing — which makes the common case the
     * one nothing checks.
     */
    public function show(Notification $notification): Shown;
}
