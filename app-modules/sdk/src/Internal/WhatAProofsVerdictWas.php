<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_key_exists;
use function is_array;
use function is_string;

use Modules\Kernel\Api\AProof;
use Modules\Kernel\Api\PluginLines;
use Modules\Kernel\Api\TheProofs;
use Modules\Kernel\Api\WhatAProofCameTo;
use Modules\Kernel\Api\WhatAProofSays;
use Modules\Sdk\Api\Fields\PluginsField;
use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\PluginsAreUnreadable;
use Modules\Sdk\Api\WireField;

use function trim;

/**
 * Every proof a plugin install states, with what asking each came to, read off the `plugins` envelope.
 *
 * Apart from {@see \Modules\Sdk\Api\PluginInstalls} for its size and its one rule: **a proof
 * with no `came_to` was not asked**, which is every proof on a reading, and it
 * is never read as passed. Each answer the stack gives is read by its own
 * word, and a word this app has no reading for is refused rather than taken
 * for any of the five.
 */
final readonly class WhatAProofsVerdictWas
{
    /**
     * Every proof, in the order the install states them.
     *
     * @param array<mixed> $install
     */
    public static function proofs(array $install): TheProofs
    {
        if (! array_key_exists(PluginsField::Proofs->value, $install) || ! is_array($install[PluginsField::Proofs->value])) {
            throw PluginsAreUnreadable::missing(PluginsField::Proofs);
        }

        $found = [];
        $position = 0;

        foreach ($install[PluginsField::Proofs->value] as $row) {
            if (! is_array($row)) {
                throw PluginsAreUnreadable::entry(PluginsField::Proofs, PluginsField::Proof, $position);
            }

            $found[] = AProof::of(
                self::text($row, PluginsField::Proof, $position),
                self::text($row, PluginsField::Establishes, $position),
                self::text($row, PluginsField::Asks, $position),
                self::text($row, WireField::Why, $position),
                self::cameTo($row, $position),
            );
            $position++;
        }

        return TheProofs::these(...$found);
    }

    /**
     * What asking one proof came to.
     *
     * @param array<mixed> $row
     */
    private static function cameTo(array $row, int $position): WhatAProofCameTo
    {
        if (! array_key_exists(PluginsField::CameTo->value, $row) || $row[PluginsField::CameTo->value] === null) {
            return WhatAProofCameTo::notAsked();
        }

        $verdict = $row[PluginsField::CameTo->value];

        if (! is_array($verdict)) {
            throw PluginsAreUnreadable::entry(PluginsField::Proofs, PluginsField::CameTo, $position);
        }

        $said = self::text($verdict, WireField::Outcome, $position);
        $outcome = WhatAProofSays::tryFrom($said);

        return match ($outcome) {
            WhatAProofSays::Passed => WhatAProofCameTo::passed(),
            WhatAProofSays::Failed => WhatAProofCameTo::failed(PluginLines::under(PluginsField::Faults->value, ...self::words($verdict, PluginsField::Faults, $position))),
            WhatAProofSays::Unproven => WhatAProofCameTo::unproven(self::text($verdict, WireField::Why, $position)),
            WhatAProofSays::FailingAsDeclared => WhatAProofCameTo::failingAsDeclared(PluginLines::under(WireField::Reason->value, ...self::reasons($verdict, $position))),
            WhatAProofSays::NotAsked, null => throw PluginsAreUnreadable::said(WireField::Outcome, $said),
        };
    }

    /**
     * Why each failure a plugin declared it would show is one, in the stack's order.
     *
     * @param  array<mixed> $verdict
     * @return list<string>
     */
    private static function reasons(array $verdict, int $position): array
    {
        if (! array_key_exists(PluginsField::Declared->value, $verdict) || ! is_array($verdict[PluginsField::Declared->value])) {
            throw PluginsAreUnreadable::entry(PluginsField::Proofs, PluginsField::Declared, $position);
        }

        $found = [];

        foreach ($verdict[PluginsField::Declared->value] as $declared) {
            if (! is_array($declared)) {
                throw PluginsAreUnreadable::entry(PluginsField::Proofs, PluginsField::Declared, $position);
            }

            $found[] = self::text($declared, WireField::Reason, $position);
        }

        return $found;
    }

    /**
     * Every word in one list.
     *
     * @param  array<mixed> $row
     * @return list<string>
     */
    private static function words(array $row, NamesAWireField $field, int $position): array
    {
        if (! array_key_exists($field->value, $row) || ! is_array($row[$field->value])) {
            throw PluginsAreUnreadable::entry(PluginsField::Proofs, $field, $position);
        }

        $found = [];

        foreach ($row[$field->value] as $word) {
            if (! is_string($word) || trim($word) === '') {
                throw PluginsAreUnreadable::entry(PluginsField::Proofs, $field, $position);
            }

            $found[] = $word;
        }

        return $found;
    }

    /**
     * A word a proof must carry.
     *
     * @param array<mixed> $row
     */
    private static function text(array $row, NamesAWireField $field, int $position): string
    {
        if (! array_key_exists($field->value, $row) || ! is_string($row[$field->value]) || trim($row[$field->value]) === '') {
            throw PluginsAreUnreadable::entry(PluginsField::Proofs, $field, $position);
        }

        return $row[$field->value];
    }
}
