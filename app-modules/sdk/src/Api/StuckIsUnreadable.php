<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_map;
use function implode;

use InvalidArgumentException;
use Modules\Kernel\Api\Stage;

use function sprintf;

/**
 * The `stuck` envelope did not hold what the contract says it holds.
 *
 * The same refusal {@see HouseholdIsUnreadable} is, for a stall's payload, and
 * for its reason: every one of these is a bug somewhere other than here, and
 * the message names the field and what arrived because that is the only thing
 * that shortens the search. A developer reads it, so it is `sprintf` and never
 * translated (`L1`).
 *
 * **A stalled listing is worth refusing rather than salvaging**, and for a
 * sharper reason than a household is. A listing one row short reads as one
 * fewer thing stuck — which is the direction of error nobody investigates. An
 * operator who is shown three stalled titles and told that is all of them stops
 * looking; the fourth stays where it is until somebody in the house asks why
 * the film they wanted never arrived.
 */
final class StuckIsUnreadable extends InvalidArgumentException
{
    public static function missing(NamesAWireField $field): self
    {
        return new self(sprintf(
            'The stuck envelope has no `%s`, or it is not what the contract says it is. This answer did not come from a lemonfiber of a version this app can read.',
            $field->value,
        ));
    }

    public static function item(int $position): self
    {
        return new self(sprintf(
            'Item %d in the stuck envelope is not a stalled item. It is refused rather than dropped: a listing one row short reads as one fewer thing stuck, which is the direction of error nobody goes looking for.',
            $position,
        ));
    }

    public static function said(NamesAWireField $field, int $position): self
    {
        return new self(sprintf(
            'Item %d in the stuck envelope has no readable `%s`. A row missing it is a row an operator cannot act on, and showing it anyway asks somebody to chase a blank.',
            $position,
            $field->value,
        ));
    }

    public static function stage(string $said, int $position): self
    {
        // The accepted list comes from the enum rather than from a sentence
        // written here, so a case added to the contract cannot leave this
        // message describing the old vocabulary — the argument
        // {@see HouseholdIsUnreadable::standing()} makes.
        return new self(sprintf(
            'Item %d in the stuck envelope says it is at `%s`, and this app reads %s. Guessing which was meant is how a title nothing is watching for gets shown as one that is on its way.',
            $position,
            $said,
            implode(', ', array_map(
                static fn(Stage $stage): string => sprintf('`%s`', $stage->value),
                Stage::cases(),
            )),
        ));
    }
}
