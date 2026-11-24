<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Which words a notification is shown in: a condition starting, one resolving, a stack with more to tell than is shown, or some alerts from a stack that may be missing.
 */
enum HowItIsWorded: string
{
    case Onset = 'onset';

    case Resolved = 'resolved';

    case MoreToTell = 'more';

    case SomeMayBeMissing = 'missing';

    /** The words for an alert that went this way. */
    public static function forAnAlert(WhichWayItWent $way): self
    {
        return match ($way) {
            WhichWayItWent::Onset => self::Onset,
            WhichWayItWent::Resolved => self::Resolved,
        };
    }
}
