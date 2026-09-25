<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function array_map;
use function is_array;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\CredentialsEnvelope;
use Modules\Kernel\Api\ACredentialHeld;
use Modules\Kernel\Api\Remarks;
use Modules\Kernel\Api\TheCredentialsHeld;
use Modules\Kernel\Api\WhatTheStoreProtects;
use Modules\Kernel\Api\WhatUsesIt;
use Modules\Kernel\Api\WhereACredentialStands;
use Modules\Kernel\Api\WhoMadeACredential;
use Modules\Sdk\Api\Fields\CredentialsField;
use Modules\Sdk\Internal\Wire;

use function trim;

/**
 * Reads the `credentials` envelope into the credentials a stack holds.
 *
 * Written the way {@see WhatLeaves} is: a static fold with no state, refusing
 * by position anything the kernel would refuse, with
 * {@see CredentialsIsUnreadable}.
 *
 * **No value is read, and none could be.** A credential on the wire carries
 * none, and the one place the envelope can carry a value — what an operator
 * asked to be shown — is not read at all. Only what the requirements ask for
 * is read; the rest is recorded in `WhatTheContractCarriesThatNothingReadsTest`,
 * each with its reason.
 */
final readonly class CredentialsKept
{
    /**
     * Every credential the stack holds, with what their store protects against.
     *
     * @param Envelope<mixed> $envelope the `credentials` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): TheCredentialsHeld
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw CredentialsIsUnreadable::missing(WireField::Data);
        }

        return TheCredentialsHeld::of(self::protection($data), ...self::held($data));
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
        return CredentialsEnvelope::in($envelope)->data;
    }

    /**
     * What keeping them in files does and does not protect against.
     *
     * @param array<mixed> $data
     */
    private static function protection(array $data): WhatTheStoreProtects
    {
        if (! array_key_exists(CredentialsField::Protection->value, $data) || ! is_array($data[CredentialsField::Protection->value])) {
            throw CredentialsIsUnreadable::missing(CredentialsField::Protection);
        }

        $protection = $data[CredentialsField::Protection->value];

        if (! array_key_exists(WireField::Summary->value, $protection) || ! is_string($protection[WireField::Summary->value]) || trim($protection[WireField::Summary->value]) === '') {
            throw CredentialsIsUnreadable::missing(WireField::Summary);
        }

        return WhatTheStoreProtects::said(
            $protection[WireField::Summary->value],
            Remarks::of(...self::sentences($protection, CredentialsField::Against)),
            Remarks::of(...self::sentences($protection, CredentialsField::NotAgainst)),
        );
    }

    /**
     * One of the store's two lists, each sentence required to say something.
     *
     * @param  array<mixed> $protection
     * @return list<string>
     */
    private static function sentences(array $protection, NamesAWireField $list): array
    {
        if (! array_key_exists($list->value, $protection) || ! is_array($protection[$list->value])) {
            throw CredentialsIsUnreadable::missing($list);
        }

        $found = [];

        foreach ($protection[$list->value] as $one) {
            if (! is_string($one) || trim($one) === '') {
                throw CredentialsIsUnreadable::missing($list);
            }

            $found[] = $one;
        }

        return $found;
    }

    /**
     * Every credential, refusing any row this app cannot show.
     *
     * @param  array<mixed>          $data
     * @return list<ACredentialHeld>
     */
    private static function held(array $data): array
    {
        if (! array_key_exists(CredentialsField::Held->value, $data) || ! is_array($data[CredentialsField::Held->value])) {
            throw CredentialsIsUnreadable::missing(CredentialsField::Held);
        }

        $found = [];
        $position = 0;

        foreach ($data[CredentialsField::Held->value] as $row) {
            if (! is_array($row)) {
                throw CredentialsIsUnreadable::row($position);
            }

            $found[] = ACredentialHeld::described(
                self::text($row, WireField::Name, $position),
                self::state($row, $position),
                self::origin($row, $position),
                WhatUsesIt::of(...self::consumers($row, $position)),
                self::advisory($row, $position),
            );
            $position++;
        }

        return $found;
    }

    /**
     * Where one credential stands.
     *
     * @param array<mixed> $row
     */
    private static function state(array $row, int $position): WhereACredentialStands
    {
        $said = self::text($row, WireField::State, $position);

        return WhereACredentialStands::tryFrom($said)
            ?? throw CredentialsIsUnreadable::word(WireField::State, $said, $position, ...array_map(static fn(WhereACredentialStands $case): string => $case->value, WhereACredentialStands::cases()));
    }

    /**
     * Who produced one credential.
     *
     * @param array<mixed> $row
     */
    private static function origin(array $row, int $position): WhoMadeACredential
    {
        $said = self::text($row, WireField::Origin, $position);

        return WhoMadeACredential::tryFrom($said)
            ?? throw CredentialsIsUnreadable::word(WireField::Origin, $said, $position, ...array_map(static fn(WhoMadeACredential $case): string => $case->value, WhoMadeACredential::cases()));
    }

    /**
     * Everything that authenticates with one credential; none at all is an answer.
     *
     * @param  array<mixed> $row
     * @return list<string>
     */
    private static function consumers(array $row, int $position): array
    {
        if (! array_key_exists(CredentialsField::Consumers->value, $row) || ! is_array($row[CredentialsField::Consumers->value])) {
            throw CredentialsIsUnreadable::said(CredentialsField::Consumers, $position);
        }

        $found = [];

        foreach ($row[CredentialsField::Consumers->value] as $one) {
            if (! is_string($one) || trim($one) === '') {
                throw CredentialsIsUnreadable::said(CredentialsField::Consumers, $position);
            }

            $found[] = $one;
        }

        return $found;
    }

    /**
     * What is worth saying about one credential: empty where absent or `null`, refused where blank or not text.
     *
     * @param array<mixed> $row
     */
    private static function advisory(array $row, int $position): string
    {
        if (! array_key_exists(CredentialsField::Advisory->value, $row) || $row[CredentialsField::Advisory->value] === null) {
            return '';
        }

        return self::text($row, CredentialsField::Advisory, $position);
    }

    /**
     * A required field of one credential, as text.
     *
     * @param array<mixed> $row
     */
    private static function text(array $row, NamesAWireField $field, int $position): string
    {
        // A guard rather than `?? null` on the subscript, which `C9` refuses.
        if (! array_key_exists($field->value, $row)) {
            throw CredentialsIsUnreadable::said($field, $position);
        }

        $said = $row[$field->value];

        if (! is_string($said) || trim($said) === '') {
            throw CredentialsIsUnreadable::said($field, $position);
        }

        return $said;
    }
}
