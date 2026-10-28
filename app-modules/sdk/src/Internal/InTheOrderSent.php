<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_values;

/**
 * The rows of a list as the stack sent them, each keyed by its position from nought.
 *
 * A list that arrived as an object is read by position, not by key, so a
 * refusal names the same row whichever shape the list came in.
 */
final readonly class InTheOrderSent
{
    /**
     * @template TRow
     *
     * @param  array<TRow> $rows
     * @return list<TRow>
     */
    public static function rows(array $rows): array
    {
        return array_values($rows);
    }
}
