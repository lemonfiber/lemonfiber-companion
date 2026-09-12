<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The device's own authentication, asked for rather than performed.
 *
 * A port because there is nobody to authenticate on a laptop. Behind it on a
 * handset is lemonfiber's own native expansion; behind it everywhere else is a
 * fake, which is what lets a test about `N4-R7` be written at all.
 *
 * **`N4-R8` is kept by the return type having no third case.** The requirement
 * has two clauses — a biometric failure falls back to the device passcode, and
 * it must not fall back to unlocked — and they are kept in different places.
 * The fallback is one constant chosen in the native half, where the platform is
 * told what it may accept; that cannot be expressed here at all. What *can* be
 * expressed here is the second clause: {@see Lock} is either `held()` or
 * `openedBy(Authenticated)`, and an `Authenticated` can only be made by
 * {@see Authenticated::byTheDevice()}. There is no value this port can answer
 * with that means "open, but nobody checked".
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
     * **Takes nothing, and that is the decision.** iOS and Android both show a
     * reason in their own dialog, so there is one sentence to supply — and a
     * `string $reason` parameter was the first shape of this method until `D2`
     * refused it. The rule was right for a better reason than the one it states:
     * a caller that may pass the sentence is a caller that may write the
     * sentence, and `L1` says the words come from the translator. With no
     * parameter there is nowhere to put a literal, and the adapter reads the
     * one line the catalogue holds.
     */
    public function unlock(): Lock;
}
