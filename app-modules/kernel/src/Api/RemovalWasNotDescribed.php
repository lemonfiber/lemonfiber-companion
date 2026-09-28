<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use LogicException;

/**
 * Taking somebody out was agreed to against something that was not a description of what it costs.
 *
 * A developer reads it: the screen only offers the yes beneath a reading
 * nobody has agreed to yet, so reaching this is a caller that skipped it.
 */
final class RemovalWasNotDescribed extends LogicException
{
    /** The answer agreed against had already taken them out. */
    public static function becauseItWasCarriedOut(): self
    {
        return new self('Taking somebody out was agreed to against an answer that had already been carried out, which is not a cost anybody was shown.');
    }
}
