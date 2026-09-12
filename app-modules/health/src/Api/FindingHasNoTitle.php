<?php

declare(strict_types=1);

namespace Modules\Health\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A finding arrived with nothing to put on the row.
 *
 * Raised where a report's row becomes a `Finding`, and it names the check
 * because that is what a report can be searched by: "one of the findings was
 * blank" cannot be followed up, and `vpn.egress-match` can (C3).
 */
final class FindingHasNoTitle extends InvalidArgumentException
{
    public static function about(Check $check): self
    {
        return new self(sprintf(
            '%s arrived with no title, so it would render as an empty row in the list an operator is scanning.',
            $check->shown(),
        ));
    }
}
