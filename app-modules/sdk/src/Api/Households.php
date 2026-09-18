<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\HouseholdEnvelope;
use Modules\Kernel\Api\Requested;
use Modules\Kernel\Api\Size;
use Modules\Kernel\Api\TurnedDown;
use Modules\Kernel\Api\Waiting;
use Modules\Kernel\Api\Wanted;
use Modules\Sdk\Internal\Wire;

use function trim;

/**
 * The `household` envelope, as the requests this app can show.
 *
 * The sibling of {@see Reports} for the household's payload, and written the same
 * way: a static fold with no state, reading through {@see WireField} so no
 * field name is spelled twice, and refusing rather than salvaging.
 *
 * **The household is flattened into one list.** The envelope nests requests
 * under the member who made them, and this app un-nests them because a request
 * carries who asked — a decline has to reach them by name — and an operator
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
     * Takes the members as they arrived rather than as a list, for
     * {@see self::requestsOf()}'s reason: this walks them and counts its own
     * position, so their keys are never read.
     *
     * @param array<mixed> $members
     */
    private static function wanted(array $members): Requested
    {
        $wanted = [];
        $position = 0;

        foreach ($members as $member) {
            if (! is_array($member)) {
                throw HouseholdIsUnreadable::member($position);
            }

            foreach (self::askedBy($member, $position) as $request) {
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
     * @param  int $at the member's place in the house, for a refusal that can be found
     * @return list<Wanted>
     */
    private static function askedBy(array $member, int $at): array
    {
        $by = self::text($member, WireField::Name);
        $wanted = [];
        $position = 0;

        foreach (self::requestsOf($member, $at) as $row) {
            if (! is_array($row)) {
                throw HouseholdIsUnreadable::request($by, $position);
            }

            $wanted[] = self::request($row, $by, $position);
            $position++;
        }

        return $wanted;
    }

    /**
     * The requests one member made.
     *
     * Deliberately not {@see self::rows()}, and the difference is the sentence
     * rather than the check. `rows()` raises the envelope-level refusal — *the
     * household envelope has no `requests`* — which is true of an envelope and
     * false of a member. A house of six whose fourth member has no requests key
     * would report as an answer from a version of lemonfiber this app cannot
     * read, and drop the one fact that would make it findable: which member.
     *
     * A member whose requests cannot be read is a member this app cannot read,
     * which is what {@see HouseholdIsUnreadable::member()} already says.
     *
     * **Returned as it arrived, keys and all.** The caller walks it and counts
     * its own position, so the keys are never read — and a reindexing nothing
     * can observe is a line held in place by the annotation above it rather
     * than by anything it does. {@see Requested::of()} keeps its own, because
     * there the list is stored and handed out again, where the keys escape.
     *
     * @param  array<mixed> $member
     * @return array<mixed>
     */
    private static function requestsOf(array $member, int $at): array
    {
        if (! array_key_exists(WireField::Requests->value, $member)) {
            throw HouseholdIsUnreadable::member($at);
        }

        $rows = $member[WireField::Requests->value];

        if (! is_array($rows)) {
            throw HouseholdIsUnreadable::member($at);
        }

        return $rows;
    }

    /**
     * A list under a named field.
     *
     * Returned as it arrived, for {@see self::requestsOf()}'s reason: the only
     * caller walks it, so nothing reads the keys.
     *
     * @param  array<mixed> $data
     * @return array<mixed>
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

        return $rows;
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

    /**
     * One request, refused or not, which are two different values.
     *
     * The branch is on the standing rather than on whether a `refused` key
     * happens to be there, because the two are tied together: a decline
     * *is* a reason, so a row saying `declined` and carrying none is a stack
     * that broke the rule and is refused here rather than shown as a word an
     * operator cannot explain to the person who asked.
     *
     * The other direction is left alone on purpose. A row that is not declined
     * and carries a refusal anyway is a stack that changed its mind, and the
     * standing is the fact this app renders — showing somebody why a thing they
     * are still waiting for was refused would be worse than dropping it.
     *
     * @param array<mixed> $row
     */
    private static function request(array $row, string $by, int $position): Wanted
    {
        $standing = self::standing($row, $by, $position);
        $number = self::number($row, $by, $position);
        $title = self::title($row, $by, $position);

        if ($standing !== Waiting::Declined) {
            return Wanted::of($number, $by, $title, self::size($row), $standing);
        }

        return Wanted::turnedDown(
            $number,
            $by,
            $title,
            self::size($row),
            self::whyItWasRefused($row, $by, $position),
        );
    }

    /**
     * What they were told, and when, where the stack said when.
     *
     * A declined row with no readable reason is refused rather than given one,
     * which is the rule exactly: the app must not substitute a value the
     * contract did not carry, and the substitute available here — *no reason
     * given* — is a sentence this application would have written on a stack's
     * behalf and shown to the person who asked.
     *
     * @param array<mixed> $row
     */
    private static function whyItWasRefused(array $row, string $by, int $position): TurnedDown
    {
        $refused = self::under($row, WireField::Refused);

        if (! is_array($refused)) {
            throw HouseholdIsUnreadable::refusal($by, $position);
        }

        // Through `under()` rather than a coalesce on the subscript: `C9` refuses
        // the shape, and substituting what it usually means is refused, which is this
        // app filling a gap the contract left.
        $reason = self::under($refused, WireField::Reason);

        if (! is_string($reason) || trim($reason) === '') {
            throw HouseholdIsUnreadable::refusal($by, $position);
        }

        $at = self::under($refused, WireField::At);

        // A refusal with no moment is ordinary — the stack records one where it
        // has one — so this is the one absence here with an answer rather than
        // a refusal, which is `Size::unknown()`'s argument on the row above.
        if (! is_string($at) || trim($at) === '') {
            return TurnedDown::because($reason);
        }

        return TurnedDown::at($at, $reason);
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
     * has sized yet is an ordinary state of a queue, and what is wanted is *we do
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
