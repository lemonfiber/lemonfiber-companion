<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function array_values;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\HouseholdEnvelope;
use Modules\Kernel\Api\Requested;
use Modules\Kernel\Api\Size;
use Modules\Kernel\Api\Waiting;
use Modules\Kernel\Api\Wanted;
use Modules\Sdk\Internal\Wire;

/**
 * The `household` envelope, as the requests this app can show.
 *
 * The sibling of {@see Reports} for `N2-R11`'s payload, and written the same
 * way: a static fold with no state, reading through {@see WireField} so no
 * field name is spelled twice, and refusing rather than salvaging.
 *
 * **The household is flattened into one list.** The envelope nests requests
 * under the member who made them, and this app un-nests them because a request
 * carries who asked (`D7-R7` has a decline reach them by name) and an operator
 * deciding on six requests is deciding on six requests, not on three people.
 * Grouping them back by member is a screen's decision and reversible; losing
 * the requester is not, which is why {@see Wanted} takes the name rather than
 * the screen reading it off a heading.
 *
 * **A request this app cannot show is refused, not dropped.** A title or a
 * state the contract permits to be absent leaves a row nobody can decide on,
 * and a list one row short reads as somebody never having asked — while the
 * person who asked is in the house and will ask again. {@see Questions} turns
 * the refusal into the obstacle an operator can act on, which is the same
 * treatment an unreadable report gets.
 */
final readonly class Households
{
    /**
     * Every request the house has made, in the order the stack listed them.
     *
     * The order is the stack's and is preserved untouched, for {@see Reports}'
     * reason: which order a person should read them in is a screen's decision,
     * made where there is a screen to make it.
     *
     * @param Envelope<mixed> $envelope the `household` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): Requested
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw HouseholdIsUnreadable::missing(WireField::Data);
        }

        return self::wanted(self::rows($data, WireField::Members));
    }

    /**
     * The payload, as it actually arrived.
     *
     * `mixed` deliberately, for {@see Reports::payload()}'s reason: the
     * generated envelope asserts its shape without checking it, and an
     * assertion is not a fact about the socket.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function payload(Envelope $envelope): mixed
    {
        return HouseholdEnvelope::in($envelope)->data;
    }

    /**
     * Every request under every member, flattened.
     *
     * @param list<mixed> $members
     */
    private static function wanted(array $members): Requested
    {
        $wanted = [];
        $position = 0;

        foreach ($members as $member) {
            if (! is_array($member)) {
                throw HouseholdIsUnreadable::member($position);
            }

            foreach (self::askedBy($member) as $request) {
                $wanted[] = $request;
            }

            $position++;
        }

        return Requested::of(...$wanted);
    }

    /**
     * What one member asked for, with their name carried onto each row.
     *
     * @param  array<mixed> $member
     * @return list<Wanted>
     */
    private static function askedBy(array $member): array
    {
        $by = self::text($member, WireField::Name);
        $wanted = [];
        $position = 0;

        foreach (self::rows($member, WireField::Requests) as $row) {
            if (! is_array($row)) {
                throw HouseholdIsUnreadable::request($by, $position);
            }

            $wanted[] = Wanted::of(
                self::number($row, $by, $position),
                $by,
                self::title($row, $by, $position),
                self::size($row),
                self::standing($row, $by, $position),
            );
            $position++;
        }

        return $wanted;
    }

    /**
     * A list under a named field.
     *
     * @param  array<mixed> $data
     * @return list<mixed>
     */
    private static function rows(array $data, WireField $field): array
    {
        if (! array_key_exists($field->value, $data)) {
            throw HouseholdIsUnreadable::missing($field);
        }

        $rows = $data[$field->value];

        if (! is_array($rows)) {
            throw HouseholdIsUnreadable::missing($field);
        }

        return array_values($rows);
    }

    /** @param array<mixed> $data */
    private static function text(array $data, WireField $field): string
    {
        if (! array_key_exists($field->value, $data)) {
            throw HouseholdIsUnreadable::missing($field);
        }

        $said = $data[$field->value];

        if (! is_string($said)) {
            throw HouseholdIsUnreadable::missing($field);
        }

        return $said;
    }

    /**
     * One field of a request, whatever it holds, or `null` where it is absent.
     *
     * `C9` refuses `??` on a subscript because it reads as a default when it is
     * really an admission that nobody knows whether the key is there. Here
     * nobody does — the contract marks three of these optional — so the absence
     * is answered once, in one place, and every caller below decides for itself
     * what an absent field means. Two of them refuse and one of them answers
     * {@see Size::unknown()}, which is the distinction a shared `??` would have
     * flattened.
     *
     * @param array<mixed> $row
     */
    private static function under(array $row, WireField $field): mixed
    {
        // Written as a guard rather than as a ternary because the two gates
        // disagree about the ternary: rector rewrites
        // `array_key_exists(...) ? $row[...] : null` to `??`, and `C9` refuses
        // `??` on a subscript. Both are right about what they see — the point
        // of `C9` is that a coalesce reads as a default when it is really an
        // admission, and the point of this method is to make that admission
        // once, in the open, where the callers can each answer it differently.
        if (! array_key_exists($field->value, $row)) {
            return null;
        }

        return $row[$field->value];
    }

    /** @param array<mixed> $row */
    private static function number(array $row, string $by, int $position): int
    {
        $said = self::under($row, WireField::Id);

        if (! is_int($said)) {
            throw HouseholdIsUnreadable::request($by, $position);
        }

        return $said;
    }

    /** @param array<mixed> $row */
    private static function title(array $row, string $by, int $position): string
    {
        $said = self::under($row, WireField::Title);

        // The contract permits this to be absent, and a row with no title is a
        // row nobody can decide on — so it is refused here rather than shown
        // blank or quietly left out. `Wanted` would refuse it a line later; this
        // refuses it with the member and the position still in hand.
        if (! is_string($said)) {
            throw HouseholdIsUnreadable::request($by, $position);
        }

        return $said;
    }

    /** @param array<mixed> $row */
    private static function standing(array $row, string $by, int $position): Waiting
    {
        $said = self::under($row, WireField::State);

        if (! is_string($said)) {
            throw HouseholdIsUnreadable::request($by, $position);
        }

        return Waiting::tryFrom($said) ?? throw HouseholdIsUnreadable::standing($said);
    }

    /**
     * How big it is thought to be, or that nobody has sized it.
     *
     * An absent estimate is {@see Size::unknown()} rather than a refusal, which
     * is the one optional field here that has a real answer: a request nothing
     * has sized yet is an ordinary state of a queue, and `D7-R3` wants *we do
     * not know* shown rather than guessed at.
     *
     * @param array<mixed> $row
     */
    private static function size(array $row): Size
    {
        $estimate = self::under($row, WireField::Estimate);

        if (! is_array($estimate)) {
            return Size::unknown();
        }

        $bytes = self::under($estimate, WireField::Bytes);
        $measured = self::under($estimate, WireField::Measured);

        if (! is_int($bytes) || ! is_bool($measured)) {
            return Size::unknown();
        }

        return $measured ? Size::measured($bytes) : Size::guessedAt($bytes);
    }
}
