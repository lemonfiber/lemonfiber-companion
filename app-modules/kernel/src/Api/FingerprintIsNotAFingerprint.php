<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * Pairing material carried something that is not a certificate fingerprint.
 *
 * Refused rather than accepted and compared later, because a fingerprint that
 * is not 64 hex characters will never match anything: pinning it would produce
 * a stack that can be paired and never reached, and the failure would arrive as
 * a refused connection rather than as a bad pairing code.
 *
 * The length is named and the value is not. A malformed fingerprint is not a
 * secret, but it arrives in the same payload as one, and a message that echoes
 * part of a pairing payload into a stack trace is a habit rather than a
 * decision.
 */
final class FingerprintIsNotAFingerprint extends InvalidArgumentException
{
    public static function ofLength(int $length): self
    {
        return new self(sprintf(
            'A certificate fingerprint is 64 hexadecimal characters and this pairing material carried %d. Nothing was pinned, because a fingerprint that cannot match would make this stack pairable and unreachable.',
            $length,
        ));
    }

    public static function notHexadecimal(): self
    {
        return new self('A certificate fingerprint is hexadecimal and this pairing material carried something else. Nothing was pinned.');
    }
}
