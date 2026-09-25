<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function array_map;
use function is_array;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\FrontDoorEnvelope;
use Modules\Kernel\Api\AnAddressToHand;
use Modules\Kernel\Api\AServiceBeside;
use Modules\Kernel\Api\HowTheDoorCameToBe;
use Modules\Kernel\Api\HowTheDoorWasChosen;
use Modules\Kernel\Api\TheFrontDoor;
use Modules\Kernel\Api\TheServicesBeside;
use Modules\Kernel\Api\WhatItFaces;
use Modules\Kernel\Api\WhereTheFrontDoorStands;
use Modules\Kernel\Api\WhereTheHouseholdBegins;
use Modules\Sdk\Api\Fields\FrontDoorField;
use Modules\Sdk\Internal\Wire;

use function trim;

/**
 * Reads the `front-door` envelope into where the household comes in.
 *
 * Written the way {@see WhatLeaves} is: a static fold with no state, refusing
 * anything the kernel would refuse, with {@see FrontDoorIsUnreadable}.
 *
 * **Every address is the stack's text, unchanged.** Nothing here builds one
 * out of a host, a port or the address this app reaches the stack at: an
 * address absent from the answer stays absent.
 *
 * **How the door was chosen is read off its tag**, and a door named on the
 * `derived` arm is not read: the tag decides, not which fields are present.
 */
final readonly class WhereTheDoorIs
{
    /**
     * The household's front door, and everything beside it.
     *
     * @param Envelope<mixed> $envelope the `front-door` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): TheFrontDoor
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw FrontDoorIsUnreadable::missing(WireField::Data);
        }

        return TheFrontDoor::reported(
            self::standing($data),
            self::text($data, WireField::Meaning),
            self::chosen($data),
            self::begins($data),
            TheServicesBeside::of(...self::beside($data)),
        );
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
        return FrontDoorEnvelope::in($envelope)->data;
    }

    /**
     * Where the door stands.
     *
     * @param array<mixed> $data
     */
    private static function standing(array $data): WhereTheFrontDoorStands
    {
        $said = self::text($data, WireField::Standing);

        return WhereTheFrontDoorStands::tryFrom($said)
            ?? throw FrontDoorIsUnreadable::word(WireField::Standing, $said, ...array_map(static fn(WhereTheFrontDoorStands $case): string => $case->value, WhereTheFrontDoorStands::cases()));
    }

    /**
     * How the door came to be, on the arm its tag names.
     *
     * @param array<mixed> $data
     */
    private static function chosen(array $data): HowTheDoorCameToBe
    {
        if (! array_key_exists(FrontDoorField::Chosen->value, $data) || ! is_array($data[FrontDoorField::Chosen->value])) {
            throw FrontDoorIsUnreadable::missing(FrontDoorField::Chosen);
        }

        $chosen = $data[FrontDoorField::Chosen->value];
        $said = self::text($chosen, FrontDoorField::Chosen);
        $how = HowTheDoorWasChosen::tryFrom($said)
            ?? throw FrontDoorIsUnreadable::word(FrontDoorField::Chosen, $said, ...array_map(static fn(HowTheDoorWasChosen $case): string => $case->value, HowTheDoorWasChosen::cases()));

        return match ($how) {
            HowTheDoorWasChosen::Derived => HowTheDoorCameToBe::derived(),
            HowTheDoorWasChosen::Named => HowTheDoorCameToBe::byTheOperator(self::text($chosen, FrontDoorField::Door)),
            HowTheDoorWasChosen::Refused => self::refused($chosen),
        };
    }

    /**
     * A door the operator named and the stack refused, with what they named and why.
     *
     * @param array<mixed> $chosen
     */
    private static function refused(array $chosen): HowTheDoorCameToBe
    {
        if (! array_key_exists(FrontDoorField::Door->value, $chosen) || ! is_array($chosen[FrontDoorField::Door->value])) {
            throw FrontDoorIsUnreadable::under(FrontDoorField::Chosen, FrontDoorField::Door);
        }

        $door = $chosen[FrontDoorField::Door->value];

        return HowTheDoorCameToBe::refused(
            self::under($door, FrontDoorField::Door, WireField::Named),
            self::under($door, FrontDoorField::Door, WireField::Because),
        );
    }

    /**
     * The service the household begins at, or nowhere where the stack names none.
     *
     * A service with no facing is refused: the two are absent together or
     * present together.
     *
     * @param array<mixed> $data
     */
    private static function begins(array $data): WhereTheHouseholdBegins
    {
        if (! array_key_exists(WireField::Service->value, $data) || $data[WireField::Service->value] === null) {
            return WhereTheHouseholdBegins::nowhere();
        }

        return WhereTheHouseholdBegins::at(
            self::text($data, WireField::Service),
            self::faces(self::text($data, FrontDoorField::Facing)),
            self::address($data),
        );
    }

    /**
     * Everything else the household can reach, in the stack's order.
     *
     * @param  array<mixed>         $data
     * @return list<AServiceBeside>
     */
    private static function beside(array $data): array
    {
        if (! array_key_exists(WireField::Beside->value, $data) || ! is_array($data[WireField::Beside->value])) {
            throw FrontDoorIsUnreadable::missing(WireField::Beside);
        }

        $found = [];
        $position = 0;

        foreach ($data[WireField::Beside->value] as $row) {
            if (! is_array($row)) {
                throw FrontDoorIsUnreadable::beside(WireField::Service, $position);
            }

            $found[] = AServiceBeside::said(
                self::besideText($row, WireField::Service, $position),
                self::faces(self::besideText($row, FrontDoorField::Facing, $position)),
                self::besideText($row, WireField::Because, $position),
                self::address($row),
            );
            $position++;
        }

        return $found;
    }

    /** What a service is to the household, from the word the stack wrote. */
    private static function faces(string $said): WhatItFaces
    {
        return WhatItFaces::tryFrom($said)
            ?? throw FrontDoorIsUnreadable::word(FrontDoorField::Facing, $said, ...array_map(static fn(WhatItFaces $case): string => $case->value, WhatItFaces::cases()));
    }

    /**
     * The address the stack sent for the door or for one service beside it, or none.
     *
     * @param array<mixed> $table
     */
    private static function address(array $table): AnAddressToHand
    {
        if (! array_key_exists(FrontDoorField::Address->value, $table) || $table[FrontDoorField::Address->value] === null) {
            return AnAddressToHand::none();
        }

        $address = $table[FrontDoorField::Address->value];

        if (! is_array($address)) {
            throw FrontDoorIsUnreadable::missing(FrontDoorField::Address);
        }

        $caution = '';

        if (array_key_exists(WireField::Caution->value, $address) && $address[WireField::Caution->value] !== null) {
            $caution = self::under($address, FrontDoorField::Address, WireField::Caution);
        }

        return AnAddressToHand::at(self::under($address, FrontDoorField::Address, FrontDoorField::Url), $caution);
    }

    /**
     * A required field of one service beside the door, as text.
     *
     * @param array<mixed> $row
     */
    private static function besideText(array $row, NamesAWireField $field, int $position): string
    {
        if (! array_key_exists($field->value, $row) || ! is_string($row[$field->value]) || trim($row[$field->value]) === '') {
            throw FrontDoorIsUnreadable::beside($field, $position);
        }

        return $row[$field->value];
    }

    /**
     * A required field of a table under another, as text.
     *
     * @param array<mixed> $table
     */
    private static function under(array $table, NamesAWireField $parent, NamesAWireField $field): string
    {
        if (! array_key_exists($field->value, $table) || ! is_string($table[$field->value]) || trim($table[$field->value]) === '') {
            throw FrontDoorIsUnreadable::under($parent, $field);
        }

        return $table[$field->value];
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
            throw FrontDoorIsUnreadable::missing($field);
        }

        return $data[$field->value];
    }
}
