<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

/**
 * The words a `copying` reading's `state` may be, each with what else it carries.
 */
enum HowACopyIsGoing: string
{
    /** Writing as fast as it can. */
    case Working = 'working';

    /** Held to the operator's limit, carried as `limit`. */
    case Paced = 'paced';

    /** Nothing written for the operator's minutes, since `wrote`. */
    case Stalled = 'stalled';
}
