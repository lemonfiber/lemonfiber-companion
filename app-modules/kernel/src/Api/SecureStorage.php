<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Where a session may be kept, if anywhere.
 *
 * A session token goes in the platform's secure storage and
 * nowhere else — not application preferences, not an app-readable file, not an
 * unencrypted backup. Where the device offers no secure
 * storage, the app refuses to persist a session **and says why**.
 *
 * That second clause is what makes this a port rather than a call. An adapter
 * that answered "could not save" would leave the app with a session it holds
 * and cannot keep, and no way to tell an operator whether that is a device with
 * nowhere to put it or a store that would not open. The refusal has to carry its
 * reason to a screen, so the reason is a type — and so is the refusal.
 *
 * **`resume()` answers {@see Resumed} rather than a nullable `Session`.** This
 * port held no reader at all for as long as nothing resumed a stack, on the
 * argument that a port which both stores and returns a session invites a caller
 * to ask for one speculatively — and `Session` is the type that will not
 * let anybody print. The argument was about the *shape* of the reader rather
 * than about having one, and the shape is what answers it: a caller cannot pull
 * a session out of `Resumed` without saying what happens when there is none, so
 * a speculative read is a read that has to name its own else-branch.
 */
interface SecureStorage
{
    /**
     * Whether this device has somewhere a session may legitimately go.
     *
     * Asked before a session exists, so that the refusal happens at
     * pairing — where an operator is already being told how this works — rather
     * than after a successful sign-in that then cannot be remembered.
     */
    public function isAvailable(): bool;

    /**
     * Keep a session, or refuse and say why.
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

    /**
     * The session this device holds for one stack, if it holds one.
     *
     * Per stack, because they are kept separate and a reader taking no
     * argument would be the place two stacks come to share one session.
     *
     * **A store that will not open answers `notHeld()` rather than raising.**
     * As far as this question goes a keychain that cannot be read is a keychain
     * with no session in it: the operator is asked for the password, which is
     * both the honest outcome and the only useful one. The two refusals
     * are told apart where a session is being *kept*, because the remedies
     * differ there; here there is one remedy.
     */
    public function resume(StackId $stack): Resumed;
}
