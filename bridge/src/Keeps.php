<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

/**
 * Somewhere values may be kept, read back and forgotten.
 *
 * The device's secure store as the things above it need one: four questions in
 * keys and strings, with no notion of a session, a stack or a verdict. {@see
 * Storage} is what a shipped build resolves this to, and the only implementation
 * that reaches a device.
 *
 * **A role rather than a second port.** The application's own port for this is
 * `Modules\Kernel\Api\SecureStorage`, which speaks in sessions and refusals a
 * screen can show. This speaks in keys and strings. An adapter sits between
 * them and has one on each side — it implements the application's port and
 * consumes this — which is the ordinary shape rather than two abstractions over
 * one thing.
 *
 * **It exists because three adapters share one store and one of them has to be
 * replaceable.** `PlatformKeychain`, `PlatformStacks` and `PlatformVerdicts`
 * all write here, and a development build runs every one of them over a store
 * that touches no device — so the real key separation, the real JSON and the
 * real re-pairing rule are what a laptop exercises. Substituting below this,
 * at the bridge call itself, is the other way to arrange it and it is wrong
 * here: that seam is global, so a stand-in contracted to replace the store
 * would silently replace notifications and scanning too.
 */
interface Keeps
{
    /**
     * Whether this device has a secure store at all.
     *
     * Answered rather than inferred from a write that failed, because the
     * caller asking has no value in hand yet — the question is put before a
     * session exists, so that a device with nowhere to keep one refuses at
     * pairing rather than after a sign-in that then cannot be remembered.
     */
    public function canBeAsked(): bool;

    /**
     * Keep one value under one key, or say why not.
     *
     * When it may be read again is asked for rather than assumed. The platforms
     * disagree about whether they can honour it, so {@see Wrote} answers back
     * with what was actually given rather than echoing the request.
     */
    public function keep(string $key, string $value, WhenAValueMayBeRead $when): Wrote;

    /**
     * What is held under one key, in three answers rather than two.
     *
     * Found, there is no such key, and the store could not be asked. The last
     * two arrive as the same emptiness from anything returning a nullable
     * string and they are opposite answers, which is why this returns a shape a
     * caller cannot read without saying what happens in all three.
     */
    public function read(string $key): WasRead;

    /**
     * Forget whatever is held under one key.
     *
     * Answers {@see Wrote} as keeping does, so that a caller which wants to
     * know can ask — though the ordinary case after a refusal is a caller that
     * does not, because getting rid of a value cannot depend on the store that
     * just refused to take it.
     */
    public function forget(string $key): Wrote;
}
