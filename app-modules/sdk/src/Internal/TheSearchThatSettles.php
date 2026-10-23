<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_key_exists;
use function is_array;
use function is_string;

use Modules\Kernel\Api\ASearchThatSettles;
use Modules\Kernel\Api\WhetherASearchSettles;
use Modules\Sdk\Api\Fields\TraceField;
use Modules\Sdk\Api\TraceIsUnreadable;

use function trim;

/**
 * Reads a trace's `searchable`: what a search that would settle it spends, and what it covers.
 *
 * Absent or `null` is a trace no search would settle. Anything else the
 * contract would not send refuses the trace.
 */
final readonly class TheSearchThatSettles
{
    /** @param array<mixed> $data the trace, as it arrived */
    public static function in(array $data): WhetherASearchSettles
    {
        if (! array_key_exists(TraceField::Searchable->value, $data) || $data[TraceField::Searchable->value] === null) {
            return WhetherASearchSettles::not();
        }

        $searchable = $data[TraceField::Searchable->value];

        if (! is_array($searchable)) {
            throw TraceIsUnreadable::missing(TraceField::Searchable);
        }

        return WhetherASearchSettles::so(ASearchThatSettles::spending(
            self::said($searchable, TraceField::Spends),
            self::said($searchable, TraceField::Covers),
        ));
    }

    /** @param array<mixed> $searchable */
    private static function said(array $searchable, TraceField $field): string
    {
        if (! array_key_exists($field->value, $searchable) || ! is_string($searchable[$field->value]) || trim($searchable[$field->value]) === '') {
            throw TraceIsUnreadable::missing($field);
        }

        return $searchable[$field->value];
    }
}
