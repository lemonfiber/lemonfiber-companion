<?php

declare(strict_types=1);

namespace Modules\Dx\Internal;

use function array_key_exists;
use function is_array;

use Modules\Sdk\Api\Fields\PartWayField;

/**
 * What a member was part-way through on a stand-in stack, as a reader can take it: with no front door.
 *
 * The `part-way` envelope's own declaration, corrected where
 * {@see ATitleAsItReads} corrects a title's: a synthesised location and
 * fingerprint are words no door serves, and a stand-in has no door to stream
 * through, so each item carries a reason in the place of a location.
 */
final readonly class WhereTheyLeftOffAsItReads
{
    /**
     * The envelope, corrected.
     *
     * @param array<string, mixed> $envelope the envelope as synthesised from the declaration
     *
     * @return array<string, mixed>
     */
    public static function from(array $envelope): array
    {
        $data = $envelope['data'];
        // One line: `data` is always an array.
        $report = is_array($data) ? $data : [];
        $under = PartWayField::PartWay->value;
        $unlocated = [];

        foreach (array_key_exists($under, $report) && is_array($report[$under]) ? $report[$under] : [] as $item) {
            $unlocated[] = ATitleAsItReads::unlocated(is_array($item) ? $item : []);
        }

        $report[$under] = $unlocated;
        $envelope['data'] = $report;

        return $envelope;
    }
}
