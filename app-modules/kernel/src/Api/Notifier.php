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
 * **Local, never pushed.** Every notification originates in the
 * core's decisions, and what one may carry is bounded — but the
 * reason this port exists at all, rather than a push subscription, is narrower
 * than any of them. A push notification's payload travels through Google's or
 * Apple's relay to reach the handset, which is a third party reading what a
 * stack said about somebody's home. {@see \Tests\Arch\NothingLeavesThisDeviceTest}
 * is the rule; a local notification is the implementation that keeps it, because
 * it never leaves the device it is displayed on.
 *
 * **Permission is two questions, not one.** {@see self::standing()} reads what
 * the operator has said; {@see self::ask()} raises the prompt. They were one
 * `isPermitted()` that did both, which meant every notification re-asked
 * somebody who had already declined — the behaviour that is forbidden — and it read
 * as correct because the platform usually suppresses the second dialog itself.
 * A `MUST NOT` kept by the operating system's good manners is not kept.
 *
 * **Whether it is shown is the caller's, and deliberately so.** Whether a stack is still
 * configured is a fact about this device, not about the notification centre, and
 * an adapter asking it would need the whole list of stacks to display one alert.
 * {@see Notification::concernsOneOf()} is where that question is answered, and
 * {@see WhyNothingIsShown::TheStackIsGone} is how the answer is reported back
 * through the same type as every other refusal.
 */
interface Notifier
{
    /**
     * What the operator has already said about notifications.
     *
     * Reads the answer and never raises a prompt, which is the whole of
     * A declined permission must not be requested again
     * automatically, and a method that asks in order to report cannot keep
     * that promise. {@see Asked} is the type because "never asked" and
     * "declined" are opposite answers to "should I ask?" and identical
     * answers to "may I proceed?" — a `bool` can only carry one of those
     * two questions, and it is always the wrong one at some call site.
     */
    public function standing(): Asked;

    /**
     * Ask the operator, once, at the point of first use.
     *
     * Separate from {@see self::standing()} so that raising a system prompt is
     * something a screen decides to do rather than a side effect of wanting to
     * know. The prompt belongs at first use and not on launch, and
     * the app explains itself first — both are decisions for a
     * screen with something to show, and neither is available to an adapter
     * reached from a notification that has already arrived.
     *
     * Answers what the operator said, and answers without asking where
     * {@see Asked::mayAsk()} is false — so a caller that has not checked
     * cannot accidentally re-prompt somebody who already declined.
     */
    public function ask(): Asked;

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
