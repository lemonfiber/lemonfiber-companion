<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The device's own authentication, asked for rather than performed.
 *
 * A port because there is nobody to authenticate on a laptop. Behind it on a
 * handset is lemonfiber's own native expansion; behind it everywhere else is a
 * fake, which is what lets a test about the device's own lock be written at all.
 *
 * **It is kept by the return type having no third case.** The requirement
 * has two clauses — a biometric failure falls back to the device passcode, and
 * it must not fall back to unlocked — and they are kept in different places.
 * The fallback is one constant chosen in the native half, where the platform is
 * told what it may accept; that cannot be expressed here at all. What *can* be
 * expressed here is the second clause: {@see Lock} is either `held()` or
 * `openedBy(Authenticated)`, and an `Authenticated` can only be made by
 * {@see Authenticated::byTheDevice()}, from the device's own answer. The device
 * answers open only after its prompt succeeded, after a waiver asked for by
 * whoever found the store empty, or where it has no screen lock to ask with.
 *
 * **Asking is not the same as being allowed to ask.** {@see self::isAvailable()}
 * answers whether the device has a screen lock configured at all, which is a
 * different condition from an operator who declined: one is answered by telling
 * them to set one, the other by asking again. An app that conflated them would
 * tell somebody with no passcode to try again, forever.
 */
interface DeviceAuth
{
    /**
     * Whether this device can authenticate anybody.
     *
     * False where no screen lock is configured. Asked before the app decides
     * what a refusal means, because on such a device every refusal is the same
     * refusal and no amount of asking changes it.
     */
    public function isAvailable(): bool;

    /**
     * Ask, and answer with a lock that is either held or opened.
     *
     * The device's prompt goes up now and this waits for it, so the lock that
     * comes back is what the operator did.
     *
     * **Takes nothing, and that is the decision.** iOS and Android both show a
     * reason in their own dialog, so there is one sentence to supply, and a
     * caller that may pass the sentence is a caller that may write it. With no
     * parameter there is nowhere to put a literal, and the adapter reads the
     * one line the catalogue holds.
     */
    public function unlock(): Lock;

    /**
     * Whether the lock stands right now, asked without prompting.
     *
     * The device holds the lock: it stands from a cold start, stands again once
     * the app has been away longer than the operator allows, and opens only on
     * the prompt's success. Where the device has no screen lock it never stands.
     */
    public function standing(): Lock;

    /**
     * Stand the lock down, because the store holds nothing for it to guard.
     *
     * Asked only by whoever read the store and found it empty, and answered
     * with how the lock stands afterwards.
     */
    public function waive(): Lock;

    /**
     * The lock screen is on the glass, so the device may stop covering it.
     *
     * Where it {@see WhenTheLockAsks::ByItself}, the device also raises its
     * prompt, once per time the lock stood; the answer arrives later as the lock
     * opening, and this answers how it stands now.
     */
    public function drawn(WhenTheLockAsks $asks): Lock;

    /**
     * How long the app may be out of sight before the lock stands again.
     *
     * The device measures the time away, so it is told; answered with how the
     * lock stands.
     */
    public function allowAway(HowLong $howLong): Lock;
}
