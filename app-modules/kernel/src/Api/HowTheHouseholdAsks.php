<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function sprintf;

/**
 * What happens to what the household asks for, by the stack's word for it.
 */
enum HowTheHouseholdAsks: string
{
    case Trusted = 'trusted';

    case WithinALimit = 'within-a-limit';

    case EverythingWaits = 'everything-waits';

    /** The catalogue key a choice of it is drawn with. */
    public function saidOnTheScreen(): string
    {
        return sprintf('stacks.allowance_change.policy.%s', $this->value);
    }
}
