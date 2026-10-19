<?php

declare(strict_types=1);

namespace Lemonfiber\Native;

/**
 * What throwing a phone key away came to.
 *
 * A key the phone would not delete is never read as gone.
 */
enum WhatForgettingAKeyFound
{
    /** It was there, and it is gone. */
    case Forgotten;

    /** There was none to throw away. */
    case NoneHeld;

    /** It could not be thrown away, or the phone said something this app does not read, so it may still be there. */
    case Failed;
}
