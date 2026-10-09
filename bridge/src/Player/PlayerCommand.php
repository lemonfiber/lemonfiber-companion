<?php

declare(strict_types=1);

namespace Lemonfiber\Native\Player;

/** One thing the app can ask of the player on screen, in the word both native halves read. */
enum PlayerCommand: string
{
    case Play = 'play';

    case Pause = 'pause';

    /** Go to a position, in seconds. */
    case Seek = 'seek';

    /** Play this sound track. */
    case Audio = 'audio';

    /** Show these subtitles, or none where the track is `off`. */
    case Subtitle = 'subtitle';
}
