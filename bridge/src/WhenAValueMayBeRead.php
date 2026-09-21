<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

/**
 * When a kept value may be decrypted again.
 *
 * iOS makes this choice unavoidable and Android has no equivalent knob, which
 * is exactly why it is on the wire rather than inside the iOS shim. A decision
 * with security consequences and no safe default is one both platforms should
 * be seen answering, and the Android half answers it explicitly — it reports
 * back which of these its store actually gives — rather than by omission.
 *
 * The same two words `StorageRule.kt` and `StorageRule.swift` answer with.
 */
enum WhenAValueMayBeRead: string
{
    /**
     * Only while the device is unlocked.
     *
     * The narrowest, and the right one for a session token: a value nothing can
     * read while the phone is in somebody else's hand and locked.
     */
    case WhileUnlocked = 'while_unlocked';

    /**
     * From the first unlock after a restart until the next one.
     *
     * What a value needs if anything reads it in the background. Wider than the
     * above, which is why it is asked for rather than assumed.
     */
    case AfterFirstUnlock = 'after_first_unlock';

    /**
     * What the bridge said it gave, or the narrowest where it said nothing.
     *
     * Read rather than assumed, because the two platforms do not both honour
     * what they were asked for: Android's encrypted store is readable whenever
     * the application can run, so a caller asking for the narrower one is told
     * it got the wider. An answer that reported back whatever was requested
     * would be a promise nobody kept.
     *
     * **Two branches rather than `tryFrom($said ?? '')`.** Routing *nothing
     * said* through the empty string makes it the same path as a word this
     * type does not know, and the two are not the same thing — one is a
     * machine with no device behind it, the other is a bridge that grew a case
     * since this was written. Nothing could tell them apart either: the empty
     * string is not a case here, so every unrecognised word already lands where
     * it lands and swapping one for another changes nothing anybody can
     * observe. That is a line no test can hold, which is what the mutation
     * floor said about it. `WhatTheBridgeSaidTest` drives both.
     */
    public static function orTheNarrowest(?string $said): self
    {
        if ($said === null) {
            return self::WhileUnlocked;
        }

        return self::tryFrom($said) ?? self::WhileUnlocked;
    }
}
