<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A check failed and said nothing about why.
 *
 * Refused rather than shown, for the reason {@see FindingHasNoTitle} gives: a
 * red row with no sentence is one the operator cannot act on and cannot search
 * for. A core that produces one has a fault, and the fault should be visible
 * where the payload is read rather than on somebody's screen at the point they
 * most need a sentence.
 */
final class CheckSaidNothing extends InvalidArgumentException
{
    public static function under(Code $code): self
    {
        return new self(sprintf(
            'The check reporting "%s" failed and carried no meaning, so nothing could be shown about why.',
            $code->shown(),
        ));
    }
}
