<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A repair was confirmed over a reading the app already knew was old.
 *
 * `N1-R39` keeps a retained reading from confirming an action at all, and
 * `Reading::mayConfirmAnAction()` is how a screen asks. Reaching
 * {@see Confirmed::against()} with one means the screen offered a confirmation
 * it should not have offered — so this is a fault in the surface rather than a
 * situation the operator can resolve, and there is no half-confirmed repair to
 * carry on with.
 */
final class RepairWasConfirmedAgainstAnOldReading extends InvalidArgumentException
{
    public static function of(Remedy $remedy): self
    {
        return new self(sprintf(
            'The repair "%s" was confirmed over a retained reading, which N1-R39 does not allow to confirm an action.',
            $remedy->action(),
        ));
    }
}
