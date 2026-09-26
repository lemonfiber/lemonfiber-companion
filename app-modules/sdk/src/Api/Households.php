<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function count;
use function is_array;
use function is_bool;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\HouseholdEnvelope;
use Modules\Kernel\Api\AMember;
use Modules\Kernel\Api\Requested;
use Modules\Kernel\Api\TheMembers;
use Modules\Kernel\Api\Wanted;
use Modules\Sdk\Api\Fields\HouseholdField;
use Modules\Sdk\Internal\WhatWasAskedFor;
use Modules\Sdk\Internal\Wire;

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


        // **A household the stack could not read is not an empty household.**
        // The contract carries `available` for exactly this and says so: a false
        // there is *why* the list is empty, and reading the empty list instead
        // tells somebody there is nothing waiting when the truth is that nobody
        // could find out. Refused rather than reported, so it reaches whoever is
        // looking as the obstacle it is.
        if (self::couldNotBeRead($data)) {
            throw HouseholdIsUnreadable::unread();
        }

        return self::wanted(self::rows($data, WireField::Members));
    }

    /**
     * The one member's own requests, where the answer is one member's.
     *
     * The same parsing and a different subject, which is why it is here rather
     * than in a second reader: what a request row says does not depend on who is
     * being told, and two copies of that reading would be two chances to disagree
     * about what a refusal or a missing title means.
     *
     * **Exactly one row, or nothing.** A session belonging to a member is answered
     * with that member's row because the core narrowed it, so one row is the
     * reading. Anything else is an answer about a house — the operator's read of
     * the same endpoint — and handing it to a member surface would show somebody
     * another member's requests. {@see Tellings::in()} guards its own reading the
     * same way and for the same sentence.
     *
     * **Not a filter, and that is the point of the guard rather than a detail of
     * it.** Picking the right row out of a house would be this app deciding who is
     * looking, which is the answer the core exists to give; refusing a house
     * outright is the only reading that cannot quietly become that.
     *
     * Read before it is counted, deliberately, the way `Tellings` reads before it
     * counts: a house whose fourth member carries a row this app cannot show is a
     * stack this app cannot read, and counting first would let it through as an
     * ordinary answer about somebody else.
     *
     * @param Envelope<mixed> $envelope the `household` envelope, as the client returned it
     */
    public static function theirOwnIn(Envelope $envelope): Requested
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw HouseholdIsUnreadable::missing(WireField::Data);
        }

        // **A household the stack could not read is not an empty household**, and
        // this reading is where that would hurt most: a member would be told they
        // have asked for nothing. The guard is the same one the operator's entry
        // point above keeps, because the payload is the same payload.
        if (self::couldNotBeRead($data)) {
            throw HouseholdIsUnreadable::unread();
        }

        $members = self::rows($data, WireField::Members);
        $wanted = self::wanted($members);

        return count($members) === 1 ? $wanted : Requested::none();
    }


    /**
     * Everybody the media server holds an account for, and whether each has taken it up.
     *
     * The same payload read for its people rather than its requests. Only the
     * name and `claimed` are read: what a member may watch and ask for stays
     * the core's, for the reasons the unread rows give.
     *
     * @param Envelope<mixed> $envelope the `household` envelope, as the client returned it
     */
    public static function whoIsIn(Envelope $envelope): TheMembers
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw HouseholdIsUnreadable::missing(WireField::Data);
        }

        // Nobody listed is not nobody in, where the stack could not read the
        // media server: the same guard the requests are read behind.
        if (self::couldNotBeRead($data)) {
            throw HouseholdIsUnreadable::unread();
        }

        $members = [];
        $position = 0;

        foreach (self::rows($data, WireField::Members) as $row) {
            $members[] = self::member($row, $position);
            $position++;
        }

        return TheMembers::of(...$members);
    }

    /**
     * One member, by name, joined or still invited, or a row refused by its position.
     *
     * A member with no name, or no word on whether they have taken the account
     * up, is refused rather than dropped, for the reason every row here is.
     */
    private static function member(mixed $row, int $position): AMember
    {
        if (! is_array($row)
            || ! array_key_exists(WireField::Name->value, $row)
            || ! is_string($row[WireField::Name->value])
            || ! array_key_exists(HouseholdField::Claimed->value, $row)
            || ! is_bool($row[HouseholdField::Claimed->value])) {
            throw HouseholdIsUnreadable::member($position);
        }

        return $row[HouseholdField::Claimed->value]
            ? AMember::joined($row[WireField::Name->value])
            : AMember::stillInvited($row[WireField::Name->value]);
    }

    /**
     * Whether the stack said it could not read the household.
     *
     * Absent is not false. A payload without the field is one this app cannot
     * check, and the contract requires it of every household answer — so a
     * missing one is a stack of a version this app does not know rather than a
     * household that read cleanly, and {@see self::rows()} refuses it a moment
     * later for the same reason.
     *
     * @param array<mixed> $data
     */
    private static function couldNotBeRead(array $data): bool
    {
        return array_key_exists(WireField::Available->value, $data)
            && $data[WireField::Available->value] === false;
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

            $wanted[] = WhatWasAskedFor::request($row, $by, $position);
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
        if (! array_key_exists(HouseholdField::Requests->value, $member)) {
            throw HouseholdIsUnreadable::member($at);
        }

        $rows = $member[HouseholdField::Requests->value];

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
    private static function rows(array $data, NamesAWireField $field): array
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
    private static function text(array $data, NamesAWireField $field): string
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

}
