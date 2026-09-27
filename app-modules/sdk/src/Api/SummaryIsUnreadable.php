<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_map;
use function implode;

use InvalidArgumentException;
use Modules\Kernel\Api\HowItStands;
use Modules\Kernel\Api\Severity;

use function sprintf;

/**
 * The `dashboard` envelope's health summary did not hold what the contract says it holds.
 *
 * Refused whole rather than salvaged. The summary is the line an operator
 * trusts without reading further, and one read with a field missing reads as
 * a stack with less wrong with it than the core said.
 */
final class SummaryIsUnreadable extends InvalidArgumentException
{
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The dashboard envelope has no readable `%s` in its health summary. Every summary the contract describes carries one, so this answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    public static function inItem(int $position, NamesAWireField $field): self
    {
        return new self(sprintf(
            'Affected item %d in the health summary has no readable `%s`. A summary with an item this app cannot read is refused rather than shown one item short.',
            $position,
            $field->value,
        ));
    }

    public static function standing(string $said): self
    {
        return new self(sprintf(
            'The health summary says the stack is `%s`, and this app reads %s. Guessing which was meant is how a stack nobody could vouch for gets called healthy.',
            $said,
            implode(', ', array_map(
                static fn(HowItStands $standing): string => sprintf('`%s`', $standing->value),
                HowItStands::cases(),
            )),
        ));
    }

    public static function severity(string $said, int $position): self
    {
        return new self(sprintf(
            'Affected item %d in the health summary is `%s`, and this app reads %s.',
            $position,
            $said,
            implode(', ', array_map(
                static fn(Severity $severity): string => sprintf('`%s`', $severity->value),
                Severity::cases(),
            )),
        ));
    }
}
