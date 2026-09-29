<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Where the two seal keys are kept: the platform's secure storage, and nowhere else.
 *
 * The key that seals what the phone keeps must not sit beside what it seals.
 * On Android the framework's own key is a plain file in the application's
 * storage, next to the database, so anything that reads one reads the other;
 * the platform's secure storage is not in the application's files at all.
 *
 * **Asked for at the narrowest accessibility there is**, readable only while
 * the device is unlocked, as a session is: nothing reads what the phone keeps
 * in the background.
 *
 * **Read, or make and keep.** The key this is handed is the one kept where
 * there is none that reads, so a phone makes its keys the first time the seal
 * asks and never again while they are there. It is handed the key rather than
 * making one because randomness arrives through {@see Entropy} and nowhere
 * else.
 */
interface HoldsTheSealKeys
{
    /**
     * The key kept as `$which`, or `$fresh` kept in its place, or why neither.
     *
     * No secure storage and a store that would not open are told apart,
     * because only one of them is worth asking again.
     */
    public function readOrKeep(SealKey $which, KeyMaterial $fresh): KeyHeld;
}
