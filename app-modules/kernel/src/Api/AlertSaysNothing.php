<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

use function sprintf;

/**
 * What the operator is told about arrived saying less than it needs to.
 *
 * Refused rather than shown, for {@see RequestSaysNothing}'s reason one
 * surface along. Somebody checking what their machine will wake them for is
 * owed the preset's name, what it means and which events are set apart, and a
 * blank in any of them reads as *nothing*.
 */
final class AlertSaysNothing extends InvalidArgumentException
{
    /** One of its words was blank, named because a refusal naming nothing is the defect. */
    public static function about(string $field): self
    {
        return new self(sprintf(
            'What the operator is told about arrived with its `%s` blank, and an alert setting that will not say what it is reads as *nothing is set*.',
            $field,
        ));
    }

    /** One event kind was set apart twice, so it has two answers. */
    public static function twice(string $kind): self
    {
        return new self(sprintf(
            'The event `%s` was set apart twice. One event with two answers about whether it is heard is a question with two answers.',
            $kind,
        ));
    }
}
