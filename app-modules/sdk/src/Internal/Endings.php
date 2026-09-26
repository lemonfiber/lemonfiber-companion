<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_key_exists;
use function is_array;
use function is_string;

use Modules\Kernel\Api\HowAServiceTookIt;
use Modules\Kernel\Api\HowItEnded;
use Modules\Kernel\Api\HowServicesTookIt;
use Modules\Kernel\Api\HowToUndoIt;
use Modules\Kernel\Api\ServiceId;
use Modules\Sdk\Api\Fields\UpdateField;
use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\UpkeepIsUnreadable;
use Modules\Sdk\Api\WireField;

/**
 * What became of each service an applied update touched.
 *
 * Its own reader rather than more of {@see \Modules\Sdk\Api\Standings}, for the
 * reason {@see Costs} is one: this answers a different question from the rest of
 * the payload. What is waiting is a decision an operator has yet to make, and
 * this is what happened the last time they made one — and the rule turns on
 * keeping the two apart on the screen as well as here.
 *
 * **Every field is required and none is defaulted.** The app must not substitute a
 * substituted value, and each of the three has a reassuring direction to guess
 * in: an ending nobody could read would become *updated*, a way back nobody
 * could read would become *rollback*, and both are the answer somebody would
 * stop worrying on.
 */
final readonly class Endings
{
    /**
     * The applied block, read into rows a screen can show.
     *
     * @param array<mixed> $data
     */
    public static function in(array $data): HowServicesTookIt
    {
        if (! array_key_exists(WireField::Applied->value, $data)) {
            throw UpkeepIsUnreadable::missing(WireField::Applied);
        }

        $listed = $data[WireField::Applied->value];

        if (! is_array($listed)) {
            throw UpkeepIsUnreadable::missing(WireField::Applied);
        }

        $services = [];
        $position = 0;

        foreach ($listed as $said) {
            if (! is_array($said)) {
                throw UpkeepIsUnreadable::applied($position);
            }

            $services[] = self::one($said, $position);
            ++$position;
        }

        return HowServicesTookIt::these(...$services);
    }

    /**
     * One service's row.
     *
     * @param array<mixed> $said
     */
    private static function one(array $said, int $position): HowAServiceTookIt
    {
        return HowAServiceTookIt::of(
            ServiceId::called(self::word($said, WireField::Service, $position)),
            HowItEnded::tryFrom(self::word($said, UpdateField::Ending, $position))
                ?? throw UpkeepIsUnreadable::applied($position),
            HowToUndoIt::tryFrom(self::word($said, WireField::Reversal, $position))
                ?? throw UpkeepIsUnreadable::applied($position),
        );
    }

    /**
     * One field of a row, where the row carries it as a word.
     *
     * @param array<mixed> $said
     */
    private static function word(array $said, NamesAWireField $field, int $position): string
    {
        if (! array_key_exists($field->value, $said)) {
            throw UpkeepIsUnreadable::applied($position);
        }

        $held = $said[$field->value];

        return is_string($held) ? $held : throw UpkeepIsUnreadable::applied($position);
    }
}
