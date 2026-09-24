<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * The `alerts` envelope did not hold what the contract says it holds.
 *
 * {@see OutboundIsUnreadable}'s refusal, for what the operator is told about.
 * A developer reads it, so it is `sprintf` and never translated (`L1`).
 *
 * **An exception dropped for being unreadable is refused rather than
 * dropped**, because the screen would then say an event follows the preset
 * when the operator set it apart — the difference between being woken and not.
 */
final class AlertsAreUnreadable extends InvalidArgumentException
{
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The alerts envelope has no readable `%s`. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    public static function exception(int $position): self
    {
        return new self(sprintf(
            'Exception %d in the alerts envelope is not an event set apart. It is refused rather than dropped: a list one short says an event follows the preset when the operator set it apart.',
            $position,
        ));
    }

    public static function said(NamesAWireField $field, int $position): self
    {
        return new self(sprintf(
            'Exception %d in the alerts envelope has no readable `%s`.',
            $position,
            $field->value,
        ));
    }

    /** One kind arrived twice, refused here so it never reaches the kernel as a raise. */
    public static function twice(string $kind, int $position): self
    {
        return new self(sprintf(
            'Exception %d in the alerts envelope sets `%s` apart again. One event with two answers about whether it is heard is a question with two answers.',
            $position,
            $kind,
        ));
    }
}
