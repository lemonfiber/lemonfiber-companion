<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function array_map;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\InvitationEnvelope;
use Modules\Kernel\Api\AnAddressToHand;
use Modules\Kernel\Api\AnInvitation;
use Modules\Kernel\Api\AnInvitationToHand;
use Modules\Kernel\Api\TheLibraries;
use Modules\Kernel\Api\WhatBecomesOfUnrated;
use Modules\Kernel\Api\WhatWasGranted;
use Modules\Kernel\Api\WhereTheInvitationStands;
use Modules\Kernel\Api\WhetherTheyCanAsk;
use Modules\Kernel\Api\WhoWasTakenBack;
use Modules\Sdk\Api\Fields\InvitationField;
use Modules\Sdk\Internal\Wire;

use function trim;

/**
 * Reads the `invitation` envelope into what asking somebody in came to.
 *
 * Written the way {@see WhereTheDoorIs} is: a static fold with no state,
 * refusing anything the kernel would refuse, with {@see InvitationIsUnreadable}.
 *
 * **The address is the stack's text, unchanged**, and its caution travels with
 * it. **`rehearsed` decides which constructor answers**, so an invitation that
 * made nothing is never read as an account that exists. **What was granted is
 * read only where it was sent**: absent is an offer that wrote nothing about
 * access, which the kernel keeps apart from one that wrote no restrictions.
 */
final readonly class Invitations
{
    /**
     * The invitation, as the stack answered it.
     *
     * @param Envelope<mixed> $envelope the `invitation` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): AnInvitation
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw InvitationIsUnreadable::missing(WireField::Data);
        }

        $toHand = AnInvitationToHand::to(self::text($data, WireField::Name), self::address($data), self::hours($data));
        $standing = self::standing($data);
        $linked = self::linked(self::text($data, InvitationField::Linked), InvitationField::Linked);
        $withdrawn = WhoWasTakenBack::of(...self::names($data, WireField::Withdrawn));

        $invitation = self::rehearsed($data)
            ? AnInvitation::rehearsed($toHand, $standing, $linked, $withdrawn)
            : AnInvitation::carriedOut($toHand, $standing, $linked, $withdrawn);

        if (! array_key_exists(WireField::Applied->value, $data) || $data[WireField::Applied->value] === null) {
            return $invitation;
        }

        return $invitation->granting(self::granted($data[WireField::Applied->value]));
    }

    /**
     * The payload, as it actually arrived.
     *
     * `mixed` deliberately, for {@see Records::payload()}'s reason.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function payload(Envelope $envelope): mixed
    {
        return InvitationEnvelope::in($envelope)->data;
    }

    /**
     * The one address to send them, with the stack's caution about it where it has one.
     *
     * @param array<mixed> $data
     */
    private static function address(array $data): AnAddressToHand
    {
        $caution = '';

        if (array_key_exists(WireField::Caution->value, $data) && $data[WireField::Caution->value] !== null) {
            $caution = self::text($data, WireField::Caution);
        }

        return AnAddressToHand::at(self::text($data, WireField::Address), $caution);
    }

    /**
     * How many hours it stands.
     *
     * @param array<mixed> $data
     */
    private static function hours(array $data): int
    {
        if (! array_key_exists(InvitationField::Hours->value, $data) || ! is_int($data[InvitationField::Hours->value])) {
            throw InvitationIsUnreadable::missing(InvitationField::Hours);
        }

        return $data[InvitationField::Hours->value];
    }

    /**
     * Whether it only described the invitation.
     *
     * @param array<mixed> $data
     */
    private static function rehearsed(array $data): bool
    {
        if (! array_key_exists(InvitationField::Rehearsed->value, $data) || ! is_bool($data[InvitationField::Rehearsed->value])) {
            throw InvitationIsUnreadable::missing(InvitationField::Rehearsed);
        }

        return $data[InvitationField::Rehearsed->value];
    }

    /**
     * What the stack found where it was going.
     *
     * @param array<mixed> $data
     */
    private static function standing(array $data): WhereTheInvitationStands
    {
        $said = self::text($data, WireField::Standing);

        return WhereTheInvitationStands::tryFrom($said)
            ?? throw InvitationIsUnreadable::word(WireField::Standing, $said, ...array_map(static fn(WhereTheInvitationStands $case): string => $case->value, WhereTheInvitationStands::cases()));
    }

    /** Whether the request service knows about them, from the word the stack wrote under that field. */
    private static function linked(string $said, NamesAWireField $field): WhetherTheyCanAsk
    {
        return WhetherTheyCanAsk::tryFrom($said)
            ?? throw InvitationIsUnreadable::word($field, $said, ...array_map(static fn(WhetherTheyCanAsk $case): string => $case->value, WhetherTheyCanAsk::cases()));
    }

    /** What the invitation wrote on the account, from the table the stack sent. */
    private static function granted(mixed $applied): WhatWasGranted
    {
        if (! is_array($applied)) {
            throw InvitationIsUnreadable::missing(WireField::Applied);
        }

        $unrated = self::under($applied, InvitationField::Unrated);
        $limit = '';

        if (array_key_exists(WireField::Limit->value, $applied) && $applied[WireField::Limit->value] !== null) {
            $limit = self::under($applied, WireField::Limit);
        }

        return WhatWasGranted::granted(
            TheLibraries::of(...self::libraries($applied)),
            WhatBecomesOfUnrated::tryFrom($unrated)
                ?? throw InvitationIsUnreadable::word(InvitationField::Unrated, $unrated, ...array_map(static fn(WhatBecomesOfUnrated $case): string => $case->value, WhatBecomesOfUnrated::cases())),
            self::linked(self::under($applied, InvitationField::Requesting), InvitationField::Requesting),
            self::under($applied, InvitationField::Filtering),
            $limit,
        );
    }

    /**
     * The libraries it opens, each as the operator named it.
     *
     * @param  array<mixed> $applied
     * @return list<string>
     */
    private static function libraries(array $applied): array
    {
        if (! array_key_exists(InvitationField::Libraries->value, $applied) || ! is_array($applied[InvitationField::Libraries->value])) {
            throw InvitationIsUnreadable::under(WireField::Applied, InvitationField::Libraries);
        }

        $named = [];

        foreach ($applied[InvitationField::Libraries->value] as $library) {
            if (! is_string($library) || trim($library) === '') {
                throw InvitationIsUnreadable::under(WireField::Applied, InvitationField::Libraries);
            }

            $named[] = $library;
        }

        return $named;
    }

    /**
     * A list of names on the envelope itself, each one text.
     *
     * @param  array<mixed> $data
     * @return list<string>
     */
    private static function names(array $data, NamesAWireField $field): array
    {
        if (! array_key_exists($field->value, $data) || ! is_array($data[$field->value])) {
            throw InvitationIsUnreadable::missing($field);
        }

        $names = [];

        foreach ($data[$field->value] as $name) {
            if (! is_string($name) || trim($name) === '') {
                throw InvitationIsUnreadable::missing($field);
            }

            $names[] = $name;
        }

        return $names;
    }

    /**
     * A required field of what was granted, as text.
     *
     * @param array<mixed> $applied
     */
    private static function under(array $applied, NamesAWireField $field): string
    {
        if (! array_key_exists($field->value, $applied) || ! is_string($applied[$field->value]) || trim($applied[$field->value]) === '') {
            throw InvitationIsUnreadable::under(WireField::Applied, $field);
        }

        return $applied[$field->value];
    }

    /**
     * A required field of the envelope itself, as text.
     *
     * @param array<mixed> $data
     */
    private static function text(array $data, NamesAWireField $field): string
    {
        // A guard rather than `?? null` on the subscript, which `C9` refuses.
        if (! array_key_exists($field->value, $data) || ! is_string($data[$field->value]) || trim($data[$field->value]) === '') {
            throw InvitationIsUnreadable::missing($field);
        }

        return $data[$field->value];
    }
}
