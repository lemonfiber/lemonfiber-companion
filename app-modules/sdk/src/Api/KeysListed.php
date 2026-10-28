<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function array_map;
use function explode;
use function is_array;
use function is_bool;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\KeysEnvelope;
use Modules\Kernel\Api\AKeyForAnApp;
use Modules\Kernel\Api\AKeysName;
use Modules\Kernel\Api\AMomentAsWritten;
use Modules\Kernel\Api\HowAKeyIsScoped;
use Modules\Kernel\Api\TheKeysForApps;
use Modules\Kernel\Api\WhatAKeyAdmits;
use Modules\Kernel\Api\WhatAKeyIsFor;
use Modules\Kernel\Api\WhereAKeyStands;
use Modules\Sdk\Api\Fields\KeysField;
use Modules\Sdk\Internal\InTheOrderSent;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\Wire;

use function trim;

/**
 * Reads the `keys` envelope into the integration keys a stack lists.
 *
 * A static fold with no state, refusing by position anything the kernel would
 * refuse, with {@see KeysAreUnreadable}. No secret is read, and none could be:
 * the listing carries none. Only what the requirements ask for is read; the
 * rest is recorded in `WhatThisAppDoesNotRead`, each with its reason.
 */
final readonly class KeysListed
{
    /**
     * Every key the stack lists, with its sentence on what a purpose is worth.
     *
     * @param Envelope<mixed> $envelope the `keys` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): TheKeysForApps
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw KeysAreUnreadable::missing(WireField::Data);
        }

        if (! array_key_exists(WireField::Keys->value, $data) || ! is_array($data[WireField::Keys->value])) {
            throw KeysAreUnreadable::missing(WireField::Keys);
        }

        $keys = [];

        foreach (InTheOrderSent::rows($data[WireField::Keys->value]) as $position => $row) {
            $keys[] = self::one($row, $position);
        }

        return TheKeysForApps::of(self::purposes($data), ...$keys);
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
        return KeysEnvelope::in($envelope)->data;
    }

    /** One key, refused where any of it is unreadable. */
    private static function one(mixed $row, int $position): AKeyForAnApp
    {
        if (! is_array($row)) {
            throw KeysAreUnreadable::said(WireField::Keys, $position);
        }

        return AKeyForAnApp::listed(
            AKeysName::of(self::text($row, WireField::Name, $position)),
            self::scope(self::text($row, WireField::Scope, $position), $position),
            self::purpose($row, $position),
            self::state($row, $position),
            AMomentAsWritten::byTheStack(self::text($row, KeysField::Minted, $position)),
            self::used($row, $position),
            self::flag($row, KeysField::MemberMinted, $position),
        );
    }

    /** What a key admits, from the scope the stack wrote: `read`, `act` or `member:<name>`. */
    private static function scope(string $written, int $position): WhatAKeyAdmits
    {
        [$kind, $member] = [...explode(':', $written, 2), ''];
        $scoped = HowAKeyIsScoped::tryFrom($kind);

        return match (true) {
            $scoped === HowAKeyIsScoped::Reading && $written === $kind => WhatAKeyAdmits::reading(),
            $scoped === HowAKeyIsScoped::Acting && $written === $kind => WhatAKeyAdmits::acting(),
            $scoped === HowAKeyIsScoped::AMembersOwn && trim($member) !== '' => WhatAKeyAdmits::aMembersOwn($member),
            default => throw KeysAreUnreadable::said(WireField::Scope, $position),
        };
    }

    /** @param array<mixed> $data */
    private static function purposes(array $data): string
    {
        return Required::text($data, KeysField::Purposes, KeysAreUnreadable::missing(KeysField::Purposes));
    }

    /** @param array<mixed> $row */
    private static function purpose(array $row, int $position): WhatAKeyIsFor
    {
        $said = self::text($row, WireField::Purpose, $position);

        return WhatAKeyIsFor::tryFrom($said)
            ?? throw KeysAreUnreadable::word(WireField::Purpose, $said, $position, ...array_map(static fn(WhatAKeyIsFor $case): string => $case->value, WhatAKeyIsFor::cases()));
    }

    /** @param array<mixed> $row */
    private static function state(array $row, int $position): WhereAKeyStands
    {
        $said = self::text($row, WireField::State, $position);

        return WhereAKeyStands::tryFrom($said)
            ?? throw KeysAreUnreadable::word(WireField::State, $said, $position, ...array_map(static fn(WhereAKeyStands $case): string => $case->value, WhereAKeyStands::cases()));
    }

    /** @param array<mixed> $row */
    private static function flag(array $row, NamesAWireField $field, int $position): bool
    {
        if (! array_key_exists($field->value, $row) || ! is_bool($row[$field->value])) {
            throw KeysAreUnreadable::said($field, $position);
        }

        return $row[$field->value];
    }

    /**
     * When the key was last used, unwritten where it never was.
     *
     * @param array<mixed> $row
     */
    private static function used(array $row, int $position): AMomentAsWritten
    {
        if (! array_key_exists(WireField::Used->value, $row) || $row[WireField::Used->value] === null) {
            return AMomentAsWritten::unwritten();
        }

        return AMomentAsWritten::byTheStack(self::text($row, WireField::Used, $position));
    }

    /** @param array<mixed> $row */
    private static function text(array $row, NamesAWireField $field, int $position): string
    {
        return Required::text($row, $field, KeysAreUnreadable::said($field, $position));
    }
}
