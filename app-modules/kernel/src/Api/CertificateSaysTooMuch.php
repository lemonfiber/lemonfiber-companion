<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * The certificate a stack presents arrived describing itself in a way the stack promises it never will.
 */
final class CertificateSaysTooMuch extends InvalidArgumentException
{
    /** A text still held a control character the stack writes as a hex pair. */
    public static function controlInIt(): self
    {
        return new self('A certificate text arrived holding a control character, which the stack writes out as a hex pair. It is not shown.');
    }

    /** A count was below none. */
    public static function belowNone(string $field, int $said): self
    {
        return new self(sprintf('A certificate arrived with its `%s` at %d, which it cannot be.', $field, $said));
    }
}
