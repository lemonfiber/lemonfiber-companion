<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use LogicException;

/**
 * A yes to replacing the internal certificate with no reading of it to quote.
 *
 * Refused here, because the yes is the reading's name: without one there is
 * nothing the operator was shown for the yes to stand for.
 */
final class ACertificateReplacementWasNotRead extends LogicException
{
    /** Replacing it was agreed to before it was read. */
    public static function before(): self
    {
        return new self('Replacing the internal certificate was agreed to with no reading of it to quote.');
    }
}
