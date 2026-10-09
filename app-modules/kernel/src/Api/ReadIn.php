<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The language a member chose to read a title's subtitles in, or none.
 *
 * Each case's value is the word the phone keeps it under. For a language it
 * is also the language's tag, which is what a player matches a track by.
 */
enum ReadIn: string
{
    /** No subtitles. */
    case Nothing = 'none';

    case Dutch = 'nl';

    case English = 'en';
}
