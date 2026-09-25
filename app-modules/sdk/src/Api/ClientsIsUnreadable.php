<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_map;
use function implode;

use InvalidArgumentException;
use Modules\Kernel\Api\HowWellADeviceIsServed;
use Modules\Sdk\Api\Fields\ClientsField;

use function sprintf;

/**
 * The `clients` envelope did not hold what the contract says it holds.
 *
 * A developer reads it, so it is `sprintf` and never translated (`L1`).
 * Refused rather than salvaged: a device dropped is one somebody holding it is
 * told nothing about, and a rating read as the nearest one could call a poorly
 * served device a good one.
 */
final class ClientsIsUnreadable extends InvalidArgumentException
{
    /** A field of the envelope itself is absent, or not what the contract says it is. */
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The clients envelope has no readable `%s`. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    /** One entry of a list is not what the list holds. */
    public static function row(NamesAWireField $list, int $position): self
    {
        return new self(sprintf(
            'Entry %d of `%s` in the clients envelope is not one. It is refused rather than dropped: advice one row short reads as complete.',
            $position,
            $list->value,
        ));
    }

    /** One entry's field is absent, blank, or not what the contract says it is. */
    public static function said(NamesAWireField $list, NamesAWireField $field, int $position): self
    {
        return new self(sprintf(
            'Entry %d of `%s` in the clients envelope has no readable `%s`.',
            $position,
            $list->value,
            $field->value,
        ));
    }

    /** What strains playback is there and does not say one of the things it owes. */
    public static function strained(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The clients envelope has no readable `%s`. A caution about playback that will not say it is one nobody can act on.',
            $field->under(ClientsField::Straining),
        ));
    }

    /** A device's rating is not one this app has a case for. */
    public static function support(string $said, int $position): self
    {
        // The accepted list comes from the enum, so a rating added cannot
        // leave this message describing the old set.
        return new self(sprintf(
            'Entry %d of `devices` in the clients envelope is rated `%s`, and this app reads %s. Drawing it as the nearest one would be a guess about how well somebody\'s device is served.',
            $position,
            $said,
            implode(', ', array_map(static fn(HowWellADeviceIsServed $case): string => sprintf('`%s`', $case->value), HowWellADeviceIsServed::cases())),
        ));
    }
}
