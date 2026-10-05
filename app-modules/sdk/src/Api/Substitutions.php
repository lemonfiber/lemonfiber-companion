<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_bool;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\SubstitutionEnvelope;
use Modules\Kernel\Api\AFill;
use Modules\Kernel\Api\Capability;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\Unfilled;
use Modules\Kernel\Api\WhatNothingFills;
use Modules\Sdk\Api\Fields\SubstitutionField;
use Modules\Sdk\Internal\Wire;

use function trim;

/**
 * The `substitution` envelope, as a choice of what fills a capability.
 *
 * The sibling of {@see Links}, written the same way: a static fold with no
 * state, refusing rather than salvaging, and keeping the stack's order in
 * every list.
 *
 * **Two fields may be absent.** `was` is absent or `null` where nothing
 * answers the capability now, and `why` where nobody gave a reason; both are
 * the same answer either way.
 */
final readonly class Substitutions
{
    /**
     * The choice the stack worked out, and whether it made it.
     *
     * @param Envelope<mixed> $envelope the `substitution` envelope, as the client returned it
     */
    public static function fillIn(Envelope $envelope): AFill
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw SubstitutionIsUnreadable::missing(WireField::Data);
        }

        $choice = self::table($data, SubstitutionField::Substitution);
        $was = self::optional($choice, WireField::Was);
        $fill = self::flag($data, WireField::Applied) ? AFill::made(...) : AFill::read(...);

        return $fill(
            Capability::called(self::text($choice, WireField::Capability)),
            ServiceId::called(self::text($choice, WireField::Now)),
            $was === '' ? Services::none() : Services::these(ServiceId::called($was)),
            self::services($choice),
            WhatNothingFills::these(...self::unfilled($choice)),
            self::optional($choice, WireField::Why),
            self::text($data, WireField::Agreement),
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
        return SubstitutionEnvelope::in($envelope)->data;
    }

    /**
     * Every service that asks for the capability, in the order the stack sent them.
     *
     * @param array<mixed> $choice
     */
    private static function services(array $choice): Services
    {
        $found = [];
        $position = 0;

        foreach (self::rows($choice, SubstitutionField::AskedBy) as $named) {
            if (! is_string($named) || trim($named) === '') {
                throw SubstitutionIsUnreadable::entry(SubstitutionField::AskedBy, WireField::Service, $position);
            }

            $found[] = ServiceId::called($named);
            $position++;
        }

        return Services::these(...$found);
    }

    /**
     * What the choice would leave unfilled, with the service that would lose each.
     *
     * @param  array<mixed>   $choice
     * @return list<Unfilled>
     */
    private static function unfilled(array $choice): array
    {
        $found = [];
        $position = 0;

        foreach (self::rows($choice, SubstitutionField::LeavesUnfilled) as $row) {
            if (! is_array($row)) {
                throw SubstitutionIsUnreadable::entry(SubstitutionField::LeavesUnfilled, WireField::By, $position);
            }

            $found[] = Unfilled::of(
                ServiceId::called(self::entry($row, WireField::By, $position)),
                Capability::called(self::entry($row, WireField::Capability, $position)),
            );
            $position++;
        }

        return $found;
    }

    /**
     * The rows of one list, as they arrived.
     *
     * @param  array<mixed> $choice
     * @return array<mixed>
     */
    private static function rows(array $choice, NamesAWireField $list): array
    {
        if (! array_key_exists($list->value, $choice) || ! is_array($choice[$list->value])) {
            throw SubstitutionIsUnreadable::missing($list);
        }

        return $choice[$list->value];
    }

    /**
     * A table under one field, refused where it is not one.
     *
     * @param  array<mixed> $data
     * @return array<mixed>
     */
    private static function table(array $data, NamesAWireField $field): array
    {
        if (! array_key_exists($field->value, $data) || ! is_array($data[$field->value])) {
            throw SubstitutionIsUnreadable::missing($field);
        }

        return $data[$field->value];
    }

    /**
     * A named field, as text an operator can be shown.
     *
     * @param array<mixed> $data
     */
    private static function text(array $data, NamesAWireField $field): string
    {
        if (! array_key_exists($field->value, $data) || ! is_string($data[$field->value]) || trim($data[$field->value]) === '') {
            throw SubstitutionIsUnreadable::missing($field);
        }

        return $data[$field->value];
    }

    /**
     * A field that may be absent, as text; absent and `null` are both blank.
     *
     * @param array<mixed> $data
     */
    private static function optional(array $data, NamesAWireField $field): string
    {
        if (! array_key_exists($field->value, $data) || $data[$field->value] === null) {
            return '';
        }

        return self::text($data, $field);
    }

    /**
     * A named field of one row of `leaves_unfilled`, as text.
     *
     * @param array<mixed> $row
     */
    private static function entry(array $row, NamesAWireField $field, int $position): string
    {
        if (! array_key_exists($field->value, $row) || ! is_string($row[$field->value]) || trim($row[$field->value]) === '') {
            throw SubstitutionIsUnreadable::entry(SubstitutionField::LeavesUnfilled, $field, $position);
        }

        return $row[$field->value];
    }

    /**
     * A field that is a yes or a no.
     *
     * @param array<mixed> $data
     */
    private static function flag(array $data, NamesAWireField $field): bool
    {
        if (! array_key_exists($field->value, $data) || ! is_bool($data[$field->value])) {
            throw SubstitutionIsUnreadable::missing($field);
        }

        return $data[$field->value];
    }
}
