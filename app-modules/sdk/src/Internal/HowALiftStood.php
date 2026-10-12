<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

/**
 * The three standings of a lift of the line's limits, by the word the `bandwidth` envelope tags each with.
 */
enum HowALiftStood: string
{
    case None = 'none';

    case InForce = 'in-force';

    case Expired = 'expired';
}
