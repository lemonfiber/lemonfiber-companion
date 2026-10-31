<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * How far a replacement reached one service that holds the credential.
 */
enum HowFarAHolderWasReached: string
{
    /** It holds the replacement. */
    case Updated = 'updated';

    /** It will hold the replacement once the one thing named happens. */
    case Pending = 'pending';

    /** It could not be reached, and still holds what it held. */
    case Failed = 'failed';

    /** The catalogue key for this, as an operator reads it. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.credentials.replace.reach.%s', $this->value);
    }
}
