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
    /**
     * Named by the check it answers rather than by what it does.
     *
     * `Repair` publishes no `does()` — `N2-R4`'s three clauses leave together
     * or not at all ({@see Repair::stated()}) — and this message wants an
     * identifier rather than the sentence an operator reads. The check is the
     * identifier: it is what the repair was offered under, so it is what names
     * the screen that offered it wrongly.
     */
    public static function of(Repair $repair): self
    {
        return new self(sprintf(
            'The repair offered for "%s" was confirmed over a retained reading, which N1-R39 does not allow to confirm an action.',
            $repair->answers(),
        ));
    }
}
