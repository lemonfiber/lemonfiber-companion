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
    public static function about(RequestId $id): self
    {
        return new self(sprintf(
            'Request %s arrived with nobody named as having asked for it, so nothing could be decided about it.',
            $id->reference(),
        ));
    }

    public static function forNothing(RequestId $id, string $by): self
    {
        return new self(sprintf(
            'Request %s from %s names nothing asked for, so there is nothing to approve or decline.',
            $id->reference(),
            $by,
        ));
    }

    /** A title given to a request was blank, which is a request named nothing rather than one not named yet. */
    public static function namedBlank(): self
    {
        return new self('A request was given a blank title. One with no title yet is named by its kind instead.');
    }
}
