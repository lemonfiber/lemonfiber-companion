<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

/**
 * The keys a bridge answer holds its words under, as both native halves write them.
 *
 * One set for every call, because every call's answer is one JSON object and
 * the key a word is under is part of the envelope rather than of the call.
 * {@see WhatTheBridgeAnswered} reads an answer by these and by nothing else.
 */
enum WhatAnAnswerHolds: string
{
    /** What became of the call, in the call's own word. */
    case Outcome = 'outcome';

    /** Why nothing came of it, where the device says. */
    case Because = 'because';

    /** What a camera read. */
    case Payload = 'payload';

    /** Whether asking for the camera again could change the answer. */
    case MayAskAgain = 'may_ask_again';

    /** A value the store found. */
    case Value = 'value';

    /** When a value kept may be read again. */
    case Readable = 'readable';

    /** The notifications still to be shown. */
    case Pending = 'pending';

    /** The zone the phone's clock is set to. */
    case Zone = 'zone';

    /** Whether the window is protected from capture. */
    case Protected = 'protected';

    /** Whether this application's window is the one in front. */
    case InFront = 'inFront';

    /** Whether the device can ask who is holding it. */
    case CanAuthenticate = 'canAuthenticate';

    /** Whether the person holding it said it is them. */
    case Acknowledged = 'acknowledged';
}
