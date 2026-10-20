<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

/**
 * The outcome words the phone key functions answer with.
 *
 * Mirrors the words `PhoneKeyFunctions.kt` and `PhoneKeyFunctions.swift` write.
 */
enum HowThePhoneKeyAnswered: string
{
    /** The phone could keep a key. */
    case Can = 'can';

    /** It could not. */
    case Cannot = 'cannot';

    /** A key was made. */
    case Made = 'made';

    /** None was. */
    case NotMade = 'not_made';

    /** A signature was made. */
    case Signed = 'signed';

    /** None was. */
    case NotSigned = 'not_signed';

    /** A key was thrown away. */
    case Forgotten = 'forgotten';

    /** There was no key to throw away. */
    case NoneHeld = 'none_held';

    /** The key could not be thrown away. */
    case NotForgotten = 'not_forgotten';

    /** The key is held. */
    case Held = 'held';

    /** Whether the key is held could not be read. */
    case Unknown = 'unknown';

    /** This is what the phone is called. */
    case Named = 'named';

    /** What a call answered, where it is one of these. */
    public static function in(WhatTheBridgeAnswered $said): ?self
    {
        return self::tryFrom($said->outcome() ?? '');
    }
}
