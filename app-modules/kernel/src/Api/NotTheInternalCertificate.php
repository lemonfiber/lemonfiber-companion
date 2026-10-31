<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use LogicException;

use function sprintf;

/**
 * A certificate in force given to a credential that is not the media server's internal certificate.
 *
 * Refused, because every other credential's likeness is a likeness of a secret,
 * and none is carried here.
 */
final class NotTheInternalCertificate extends LogicException
{
    /** The credential held under that setting is not the internal certificate. */
    public static function named(string $setting): self
    {
        return new self(sprintf('The credential held under `%s` is not the internal certificate, so no certificate is in force for it.', $setting));
    }
}
