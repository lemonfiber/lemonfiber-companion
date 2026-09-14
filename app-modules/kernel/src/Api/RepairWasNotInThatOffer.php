<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A confirmation named a repair the listing it quotes never offered.
 *
 * Raised rather than answered, for {@see RepairWasConfirmedAgainstAnOldReading}'s
 * reason: there is no half-confirmed repair to carry on with, and no screen has
 * anything to render about it. It is a fault in the surface — a screen that had
 * lost track of which listing a button belonged to — and not a state of the
 * world the operator can do anything about.
 *
 * What it prevents is narrow and worth naming: confirming against one listing
 * while quoting another. The engine would see a listing it recognises and a
 * repair it was asked for, and carry out something the operator agreed to under
 * a different set of consequences.
 */
final class RepairWasNotInThatOffer extends InvalidArgumentException
{
    public static function of(Repair $repair): self
    {
        return new self(sprintf(
            'The repair answering "%s" was confirmed against a listing that did not offer it.',
            $repair->answers(),
        ));
    }
}
