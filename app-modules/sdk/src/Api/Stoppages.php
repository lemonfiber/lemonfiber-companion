<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_bool;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\StuckEnvelope;
use Modules\Kernel\Api\HowMuchIsShown;
use Modules\Kernel\Api\Stage;
use Modules\Kernel\Api\Stalled;
use Modules\Kernel\Api\Stuck;
use Modules\Sdk\Internal\Wire;

use function trim;

/**
 * The `stuck` envelope, as the listing this app can show.
 *
 * The sibling of {@see Households} for a stall's payload, and written the same
 * way: a static fold with no state, reading through {@see WireField} so no
 * field name is spelled twice, and refusing rather than salvaging.
 *
 * **How much is shown is read before any row is.** `incomplete` is the one
 * field whose absence a screen cannot notice — a listing missing it renders
 * exactly like a complete one — so it is read first and its absence is a
 * refusal rather than a default. Defaulting it to *complete* would be this app
 * inventing the most reassuring answer available, and defaulting it to
 * *incomplete* would put a warning on every screen until somebody removed the
 * warning.
 *
 * **The rows keep the stack's order.** Which order an operator should read them
 * in is a screen's decision, made where there is a screen to make it — the
 * argument {@see Reports} makes about findings.
 */
final readonly class Stoppages
{
    /**
     * What has stopped coming in, in the order the stack listed it.
     *
     * @param Envelope<mixed> $envelope the `stuck` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): Stalled
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw StuckIsUnreadable::missing(WireField::Data);
        }

        return Stalled::of(self::howMuchIsShown($data), ...self::items($data));
    }

    /**
     * The payload, as it actually arrived.
     *
     * `mixed` deliberately, for {@see Households::payload()}'s reason: the
     * generated envelope asserts its shape without checking it, and an
     * assertion is not a fact about the socket.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function payload(Envelope $envelope): mixed
    {
        return StuckEnvelope::in($envelope)->data;
    }

    /**
     * Whether this listing is the whole of what the stack holds.
     *
     * The wire says `incomplete` and this says how much is shown, which are the
     * same fact the other way up. Turning it here rather than at the screen
     * means exactly one place reads the negation, and `false` never has to be
     * understood as *yes, all of it* by somebody skimming a template.
     *
     * @param array<mixed> $data
     */
    private static function howMuchIsShown(array $data): HowMuchIsShown
    {
        if (! array_key_exists(WireField::Incomplete->value, $data)) {
            throw StuckIsUnreadable::missing(WireField::Incomplete);
        }

        $incomplete = $data[WireField::Incomplete->value];

        if (! is_bool($incomplete)) {
            throw StuckIsUnreadable::missing(WireField::Incomplete);
        }

        return $incomplete ? HowMuchIsShown::SomeOfIt : HowMuchIsShown::AllOfIt;
    }

    /**
     * Every stalled item, refusing any row this app cannot show.
     *
     * @param  array<mixed> $data
     * @return list<Stuck>
     */
    private static function items(array $data): array
    {
        $stalled = [];
        $position = 0;

        foreach (self::rows($data) as $row) {
            if (! is_array($row)) {
                throw StuckIsUnreadable::item($position);
            }

            $stalled[] = Stuck::at(
                self::text($row, WireField::Title, $position),
                self::text($row, WireField::Service, $position),
                self::stage($row, $position),
            );

            $position++;
        }

        return $stalled;
    }

    /**
     * The rows, as they arrived.
     *
     * Returned with their keys, for {@see Households::rows()}'s reason: the
     * only caller walks them and counts its own position, so a reindex here
     * would be a line nothing can observe.
     *
     * @param  array<mixed> $data
     * @return array<mixed>
     */
    private static function rows(array $data): array
    {
        if (! array_key_exists(WireField::Items->value, $data)) {
            throw StuckIsUnreadable::missing(WireField::Items);
        }

        $rows = $data[WireField::Items->value];

        if (! is_array($rows)) {
            throw StuckIsUnreadable::missing(WireField::Items);
        }

        return $rows;
    }

    /**
     * A named field of one row, as text an operator can be shown.
     *
     * Blank is refused here rather than left to {@see Stuck::at()} because the
     * position is only knowable here: the refusal that says *item 4 has no
     * title* can be acted on, and the one that says *a stalled item has no
     * title* leaves somebody reading a listing of forty looking for it.
     *
     * @param array<mixed> $row
     */
    private static function text(array $row, WireField $field, int $position): string
    {
        // A guard rather than `?? null` on the subscript, which `C9` refuses:
        // a row that carries the key and a row that does not are the same
        // refusal here, and the coalesce hides which one arrived from anybody
        // reading the line.
        if (! array_key_exists($field->value, $row)) {
            throw StuckIsUnreadable::said($field, $position);
        }

        $said = $row[$field->value];

        if (! is_string($said) || trim($said) === '') {
            throw StuckIsUnreadable::said($field, $position);
        }

        return $said;
    }

    /**
     * How far one row got, as a case rather than as the word it arrived as.
     *
     * A stage this app does not recognise is refused rather than passed
     * through, which is the contract's own distinction applied to a closed set: a
     * word this build has not heard of means the contract moved, and rendering
     * it raw would put a field value on somebody's screen.
     *
     * @param array<mixed> $row
     */
    private static function stage(array $row, int $position): Stage
    {
        $said = self::text($row, WireField::Stage, $position);

        return Stage::tryFrom($said) ?? throw StuckIsUnreadable::stage($said, $position);
    }
}
