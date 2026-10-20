<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/** What throwing away the key a phone held for a stack came to. */
enum WhatForgettingAPhoneKeyFound
{
    /** It was there, and it is gone. */
    case ThrownAway;

    /** There was none to throw away. */
    case NoneHeld;

    /** It could not be thrown away, so it may still be there. */
    case StillHeld;
}
