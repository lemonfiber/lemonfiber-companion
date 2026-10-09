<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * The language a member chose to hear a title in.
 *
 * Each case's value is the word the phone keeps it under. For a language it
 * is also the language's tag, which is what a player matches a track by.
 */
enum HearIn: string
{
    /** Whatever the title was made in: the stream's own sound. */
    case TheOriginal = 'original';

    case Dutch = 'nl';

    case English = 'en';
}
