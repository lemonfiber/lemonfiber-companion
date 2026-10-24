<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use LogicException;

use function sprintf;

/**
 * A yes to ending a grant with no reading of ending that grant to quote.
 *
 * Refused here, because the yes is the reading's name: without one there is
 * nothing the operator was shown for the yes to stand for.
 */
final class ACredentialRevokeWasNotRead extends LogicException
{
    /** Ending this grant was agreed to before it was read. */
    public static function before(ACredentialGrant $grant): self
    {
        return new self(sprintf('Ending the grant of `%s` to `%s` was agreed to with no reading of it to quote.', $grant->credential(), $grant->plugin()->named()));
    }
}
