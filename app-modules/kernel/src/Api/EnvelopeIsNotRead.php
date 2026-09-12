<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function array_map;
use function implode;

use RuntimeException;

use function sprintf;

/**
 * An answer arrived in a wire version this app does not read.
 *
 * `N1-R13` asks for both halves and this carries both: the version that
 * arrived, and the versions that are read. One without the other is the error
 * message people file bugs about — "unsupported version" tells an operator
 * nothing they can act on, and a support thread then spends two replies
 * establishing which two numbers were involved.
 *
 * A `RuntimeException` rather than a logic one. A stack ahead of its app is an
 * ordinary state of the world — the operator updated the machine and not the
 * phone — and the app's answer is to say so and offer the update, not to treat
 * it as a programming error.
 *
 * **The versions read are counted, not typed.** A case added to
 * {@see WireVersion} and not to a hand-written sentence here would leave an
 * operator being told their stack speaks a version this app cannot read, by a
 * message that no longer lists the version it just started reading.
 */
final class EnvelopeIsNotRead extends RuntimeException
{
    public static function inVersion(int $arrived): self
    {
        return new self(sprintf(
            'This answer is in wire version %d, and this app reads %s. The stack and the app are different versions of lemonfiber.',
            $arrived,
            implode(' and ', array_map(
                static fn(WireVersion $version): string => (string) $version->value,
                WireVersion::cases(),
            )),
        ));
    }
}
