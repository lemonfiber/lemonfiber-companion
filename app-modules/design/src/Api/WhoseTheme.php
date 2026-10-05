<?php

declare(strict_types=1);

namespace Modules\Design\Api;

/**
 * The two themes the companion draws with, one for each application in it.
 *
 * Whose session a screen is drawn for decides which, as it decides the
 * application a person is given; a screen drawn for no session is drawn in the
 * member's. Both are dark whatever the phone is set to.
 */
enum WhoseTheme: string
{
    /** A household member's: dark, led by artwork, with lemon as the one thing to press. */
    case Member = 'member';

    /** The operator's: the web console's language on a phone, on ink with hairline rules. */
    case Operator = 'operator';
}
