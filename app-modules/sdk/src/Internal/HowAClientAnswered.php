<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

/**
 * The two shapes a download client's answer about its limits arrives in, by the word the `bandwidth` envelope tags each with.
 */
enum HowAClientAnswered: string
{
    case Held = 'held';

    case Silent = 'silent';
}
