<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A request arrived with nobody asking, or nothing asked for.
 *
 * Refused rather than shown, for {@see FindingHasNoTitle}'s reason and a
 * sharper one: this is a row an operator is being asked to make a decision on.
 * A blank where the requester belongs makes it *somebody wants something*, and
 * A decline reaches the requester by name — a decision recorded
 * against nobody cannot.
 */
final class RequestHasNobodyBehindIt extends InvalidArgumentException
{
    public static function atNumber(int $number): self
    {
        return new self(sprintf(
            'Request %d arrived with nobody named as having asked for it, so nothing could be decided about it.',
            $number,
        ));
    }

    public static function forNothing(int $number, string $by): self
    {
        return new self(sprintf(
            'Request %d from %s names nothing asked for, so there is nothing to approve or decline.',
            $number,
            $by,
        ));
    }
}
