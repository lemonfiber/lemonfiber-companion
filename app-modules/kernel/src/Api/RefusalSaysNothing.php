<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * A refusal arrived without the sentence the operator reads.
 *
 * Raised where the wire becomes a `Refusal`, and it carries the code because
 * that is the one field still worth having: it is what somebody searches for,
 * and a report saying only "a refusal was empty" cannot be followed up.
 *
 * Thrown rather than returned for the same reason `CodeIsBlank` is — this is a
 * value that cannot be constructed, not a refusal crossing a boundary (C1, C3).
 */
final class RefusalSaysNothing extends InvalidArgumentException
{
    public static function about(Code $code): self
    {
        return new self(sprintf(
            '%s arrived with no summary or no meaning, so a screen would show a heading with nothing under it.',
            $code->shown(),
        ));
    }
}
