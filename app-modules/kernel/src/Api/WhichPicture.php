<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/** Which of a title's pictures is drawn, by where the core serves it. */
enum WhichPicture: string
{
    /** The poster of a title the house holds. */
    case HeldPoster = 'held-poster';

    /** The backdrop of a title the house holds. */
    case HeldBackdrop = 'held-backdrop';

    /** The poster of a title a member's search offered. */
    case AskablePoster = 'askable-poster';
}
