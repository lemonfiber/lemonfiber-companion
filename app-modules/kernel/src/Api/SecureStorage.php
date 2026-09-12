<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Where a session may be kept, if anywhere.
 *
 * `N4-R5` says a session token goes in the platform's secure storage and
 * nowhere else — not application preferences, not an app-readable file, not an
 * unencrypted backup. `N4-R6` says that where the device offers no secure
 * storage, the app refuses to persist a session **and says why**.
 *
 * That second clause is what makes this a port rather than a call. An adapter
 * that answered "could not save" would leave the app with a session it holds
 * and cannot keep, and no way to tell an operator whether that is a device with
 * nowhere to put it or a store that would not open. The refusal has to carry its
 * reason to a screen, so the reason is a type — and so is the refusal.
 *
 * **There is no `read()` beside `keep()` here on purpose.** A port that both
 * stores and returns a session invites a caller to ask for one speculatively,
 * and `Session` is the type `N1-R15` will not let anybody print — the fewer
 * places it can be conjured from, the better. Reading it back belongs to
 * whatever resumes a paired stack, and that is the one reader.
 */
interface SecureStorage
{
    /**
     * Whether this device has somewhere a session may legitimately go.
     *
     * Asked before a session exists, so that `N4-R6`'s refusal happens at
     * pairing — where an operator is already being told how this works — rather
     * than after a successful sign-in that then cannot be remembered.
     */
    public function isAvailable(): bool;

    /**
     * Keep a session, or refuse and say why (`N4-R6`).
     *
     * Answers with {@see Kept} rather than raising, which `C1` requires and
     * which is right for a second reason: a device with no secure storage is an
     * ordinary state of the world, and a method returning nothing can only
     * report one by throwing — making the common case the one nothing checks.
     */
    public function keep(StackId $stack, Session $session): Kept;

    /**
     * Forget the session for one stack.
     *
     * Answers {@see Kept} as well, and always `safely()`: forgetting has to work
     * on a device where keeping did not, because a refusal leaves the app
     * holding a session it could not store and getting rid of it cannot depend
     * on the store that just refused. The shared type is what stops a caller
     * needing to know which of the two it is looking at.
     */
    public function forget(StackId $stack): Kept;
}
