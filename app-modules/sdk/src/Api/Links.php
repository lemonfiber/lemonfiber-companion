<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\WiringEnvelope;
use Modules\Kernel\Api\AClaimant;
use Modules\Kernel\Api\ALink;
use Modules\Kernel\Api\Capability;
use Modules\Kernel\Api\HowItReaches;
use Modules\Kernel\Api\HowItSettled;
use Modules\Kernel\Api\HowItWasReached;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\TheClaimants;
use Modules\Kernel\Api\TheLinks;
use Modules\Kernel\Api\Unfilled;
use Modules\Kernel\Api\WhatNothingFills;
use Modules\Kernel\Api\WhatSettledIt;
use Modules\Kernel\Api\WhoPutItThere;
use Modules\Kernel\Api\WhoSettledIt;
use Modules\Kernel\Api\WhyItWasChosen;
use Modules\Sdk\Api\Fields\WiringField;
use Modules\Sdk\Internal\Attributions;
use Modules\Sdk\Internal\Wire;

use function trim;

/**
 * The `wiring` envelope, as what the stack wires to what.
 *
 * The sibling of {@see Catalogues}, written the same way: a static fold with no
 * state, refusing rather than salvaging. Every list keeps the stack's order,
 * and every tagged arm is read on the word it names, so a contest cannot be
 * read as settled or a stack's choice as the operator's.
 *
 * **A reason is the one field that may be absent.** A recorded choice carries
 * `why` only where the chooser said one, and absent and `null` are the same
 * answer: nobody did.
 */
final readonly class Links
{
    /**
     * Every link the stack declares, and every capability nothing fills.
     *
     * @param Envelope<mixed> $envelope the `wiring` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): TheLinks
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw LinksAreUnreadable::missing(WireField::Data);
        }

        return TheLinks::of(WhatNothingFills::these(...self::unfilled($data)), ...self::wired($data));
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
        return WiringEnvelope::in($envelope)->data;
    }

    /**
     * Every link, refusing any row this app cannot show.
     *
     * @param  array<mixed> $data
     * @return list<ALink>
     */
    private static function wired(array $data): array
    {
        $found = [];
        $position = 0;

        foreach (self::rows($data, WiringField::Wired) as $row) {
            if (! is_array($row)) {
                throw LinksAreUnreadable::entry(WiringField::Wired, WiringField::Reaches, $position);
            }

            $found[] = ALink::from(
                ServiceId::called(self::text($row, WiringField::Wired, WireField::By, $position)),
                self::reaches(self::table($row, WiringField::Reaches, $position), $position),
            );
            $position++;
        }

        return $found;
    }

    /**
     * What one link reaches, on the arm its word names.
     *
     * @param array<mixed> $reaches
     */
    private static function reaches(array $reaches, int $position): HowItReaches
    {
        $said = self::text($reaches, WiringField::Wired, WiringField::How, $position);

        return match (HowItWasReached::tryFrom($said) ?? throw LinksAreUnreadable::unnamed(WiringField::How, $said, $position)) {
            HowItWasReached::ByName => HowItReaches::byName(ServiceId::called(self::text($reaches, WiringField::Wired, WireField::Service, $position)), self::text($reaches, WiringField::Wired, WireField::Why, $position)),
            HowItWasReached::Asked => HowItReaches::asked(
                Capability::called(self::text($reaches, WiringField::Wired, WiringField::Capability, $position)),
                self::services($reaches, WireField::Services, $position),
                self::settled($reaches, $position),
                self::claimants($reaches, $position),
            ),
        };
    }

    /**
     * How an ask was settled, on the arm its word names.
     *
     * @param array<mixed> $reaches
     */
    private static function settled(array $reaches, int $position): WhatSettledIt
    {
        $settled = self::table($reaches, WiringField::Settled, $position);
        $said = self::text($settled, WiringField::Wired, WiringField::Settled, $position);

        return match (HowItSettled::tryFrom($said) ?? throw LinksAreUnreadable::unnamed(WiringField::Settled, $said, $position)) {
            HowItSettled::Outright => WhatSettledIt::outright(),
            HowItSettled::Each => WhatSettledIt::each(),
            HowItSettled::Contested => WhatSettledIt::contested(self::services($settled, WiringField::Claimants, $position)),
            HowItSettled::Chosen => self::chosen($settled, $position),
            HowItSettled::Unfilled => WhatSettledIt::unfilled(),
        };
    }

    /**
     * A recorded choice: what it was made over, who made it, and why where they said.
     *
     * @param array<mixed> $settled
     */
    private static function chosen(array $settled, int $position): WhatSettledIt
    {
        $said = self::text($settled, WiringField::Wired, WiringField::Whose, $position);
        $whose = WhoSettledIt::tryFrom($said) ?? throw LinksAreUnreadable::unnamed(WiringField::Whose, $said, $position);
        $why = ! array_key_exists(WireField::Why->value, $settled) || $settled[WireField::Why->value] === null
            ? WhyItWasChosen::unstated()
            : WhyItWasChosen::stated(self::text($settled, WiringField::Wired, WireField::Why, $position));

        return WhatSettledIt::chosen(self::services($settled, WiringField::Over, $position), $whose, $why);
    }

    /**
     * Every service that claims the capability, with where it came from, in the order the stack sent them.
     *
     * @param array<mixed> $reaches
     */
    private static function claimants(array $reaches, int $position): TheClaimants
    {
        $found = [];

        foreach (self::table($reaches, WiringField::Origins, $position) as $service => $origin) {
            if (! is_array($origin) || trim((string) $service) === '') {
                throw LinksAreUnreadable::entry(WiringField::Wired, WiringField::Origins, $position);
            }

            $found[] = AClaimant::of(ServiceId::called((string) $service), self::origin($origin, $position));
        }

        return TheClaimants::these(...$found);
    }

    /**
     * Where one claimant came from, refused in this envelope's words where it cannot be read.
     *
     * @param array<mixed> $origin
     */
    private static function origin(array $origin, int $position): WhoPutItThere
    {
        try {
            return Attributions::table($origin);
        } catch (OriginIsUnreadable $why) {
            throw LinksAreUnreadable::origin($position, $why);
        }
    }

    /**
     * Every capability nothing fills, with the service that asked for it.
     *
     * @param  array<mixed>   $data
     * @return list<Unfilled>
     */
    private static function unfilled(array $data): array
    {
        $found = [];
        $position = 0;

        foreach (self::rows($data, WiringField::Unfilled) as $row) {
            if (! is_array($row)) {
                throw LinksAreUnreadable::entry(WiringField::Unfilled, WireField::By, $position);
            }

            $found[] = Unfilled::of(
                ServiceId::called(self::text($row, WiringField::Unfilled, WireField::By, $position)),
                Capability::called(self::text($row, WiringField::Unfilled, WiringField::Capability, $position)),
            );
            $position++;
        }

        return $found;
    }

    /**
     * A list of service names under one field, in the order the stack sent them.
     *
     * @param array<mixed> $row
     */
    private static function services(array $row, NamesAWireField $field, int $position): Services
    {
        $found = [];

        foreach (self::rows($row, $field) as $named) {
            if (! is_string($named) || trim($named) === '') {
                throw LinksAreUnreadable::entry(WiringField::Wired, $field, $position);
            }

            $found[] = ServiceId::called($named);
        }

        return Services::these(...$found);
    }

    /**
     * The rows of one list, as they arrived.
     *
     * @param  array<mixed> $data
     * @return array<mixed>
     */
    private static function rows(array $data, NamesAWireField $list): array
    {
        if (! array_key_exists($list->value, $data) || ! is_array($data[$list->value])) {
            throw LinksAreUnreadable::missing($list);
        }

        return $data[$list->value];
    }

    /**
     * A table one row of `wired` holds under a field, refused where it is not one.
     *
     * @param  array<mixed> $row
     * @return array<mixed>
     */
    private static function table(array $row, NamesAWireField $field, int $position): array
    {
        if (! array_key_exists($field->value, $row) || ! is_array($row[$field->value])) {
            throw LinksAreUnreadable::entry(WiringField::Wired, $field, $position);
        }

        return $row[$field->value];
    }

    /**
     * A named field, as text an operator can be shown.
     *
     * @param array<mixed> $row
     */
    private static function text(array $row, NamesAWireField $list, NamesAWireField $field, int $position): string
    {
        if (! array_key_exists($field->value, $row) || ! is_string($row[$field->value]) || trim($row[$field->value]) === '') {
            throw LinksAreUnreadable::entry($list, $field, $position);
        }

        return $row[$field->value];
    }
}
