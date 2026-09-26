<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_is_list;
use function array_key_exists;
use function is_array;
use function is_bool;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\RestoreEnvelope;
use Modules\Kernel\Api\ACopy;
use Modules\Kernel\Api\ACopyPutBack;
use Modules\Kernel\Api\ARelocation;
use Modules\Kernel\Api\WhatACopyHolds;
use Modules\Kernel\Api\WhatPuttingItBackWouldDo;
use Modules\Kernel\Api\WhatWroteACopy;
use Modules\Kernel\Api\WhereTheDataGoes;
use Modules\Sdk\Api\Fields\RestoreField;
use Modules\Sdk\Internal\Scopes;
use Modules\Sdk\Internal\Wire;

/**
 * The `restore` envelope, as what putting a copy back would do, or did.
 *
 * Every answer carries the listing; only a restore that was carried out
 * carries what it did. The listing is read for a rehearsal and the report for
 * a finished restore, and a finished restore with no report is refused rather
 * than read as a rehearsal: an operator who agreed is owed what happened.
 */
final readonly class TheRestore
{
    /**
     * What putting that copy back would do, read before anything is overwritten.
     *
     * @param Envelope<mixed> $envelope the `restore` envelope, as the client returned it
     */
    public static function listedIn(Envelope $envelope, ACopy $copy): WhatPuttingItBackWouldDo
    {
        $would = self::table(self::data($envelope), RestoreField::Would);
        $manifest = self::table($would, RestoreField::Manifest);

        return WhatPuttingItBackWouldDo::listed(
            $copy,
            self::text($would, WireField::Agreement),
            Scopes::in($manifest),
            WhatWroteACopy::of(
                self::text($manifest, RestoreField::ProductVersion),
                self::text($manifest, RestoreField::CreatedAt),
            ),
            self::contents($manifest),
            older: self::flag($would, RestoreField::Downgrade),
            data: self::whereItGoes($would, RestoreField::Relocation),
        );
    }

    /**
     * What putting the copy back did.
     *
     * @param Envelope<mixed> $envelope the `restore` envelope a finished job answered with
     */
    public static function doneIn(Envelope $envelope): ACopyPutBack
    {
        $done = self::table(self::data($envelope), RestoreField::Done);

        return ACopyPutBack::reported(
            Scopes::in($done),
            self::text($done, RestoreField::FromVersion),
            self::whereItGoes($done, RestoreField::Relocated),
        );
    }

    /**
     * The payload, checked to be a table.
     *
     * @param  Envelope<mixed> $envelope
     * @return array<array-key, mixed>
     */
    private static function data(Envelope $envelope): array
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw RestoreIsUnreadable::missing(WireField::Data);
        }

        return $data;
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
        return RestoreEnvelope::in($envelope)->data;
    }

    /**
     * What the copy holds, by the label each thing is listed under.
     *
     * The path inside the archive is not read, for {@see Scopes}' reason.
     *
     * @param array<array-key, mixed> $manifest
     */
    private static function contents(array $manifest): WhatACopyHolds
    {
        if (! array_key_exists(WireField::Members->value, $manifest)) {
            throw RestoreIsUnreadable::missing(WireField::Members);
        }

        $members = $manifest[WireField::Members->value];

        if (! is_array($members) || ! array_is_list($members)) {
            throw RestoreIsUnreadable::missing(WireField::Members);
        }

        $labels = [];

        foreach ($members as $position => $member) {
            if (! is_array($member) || ! array_key_exists(RestoreField::Label->value, $member) || ! is_string($member[RestoreField::Label->value])) {
                throw RestoreIsUnreadable::member($position);
            }

            $labels[] = $member[RestoreField::Label->value];
        }

        return WhatACopyHolds::these(...$labels);
    }

    /**
     * Where the data goes: back where it came from where the field is absent
     * or null, which is how the stack says so, and elsewhere where it names
     * both roots.
     *
     * @param array<array-key, mixed> $data
     */
    private static function whereItGoes(array $data, NamesAWireField $field): WhereTheDataGoes
    {
        if (! array_key_exists($field->value, $data) || $data[$field->value] === null) {
            return WhereTheDataGoes::whereItWas();
        }

        $moved = $data[$field->value];

        if (! is_array($moved)) {
            throw RestoreIsUnreadable::missing($field);
        }

        return WhereTheDataGoes::elsewhere(ARelocation::from(
            self::text($moved, RestoreField::Was),
            self::text($moved, RestoreField::Now),
        ));
    }

    /**
     * A table the answer must carry.
     *
     * @param  array<array-key, mixed> $data
     * @return array<array-key, mixed>
     */
    private static function table(array $data, NamesAWireField $field): array
    {
        if (! array_key_exists($field->value, $data) || ! is_array($data[$field->value])) {
            throw RestoreIsUnreadable::missing($field);
        }

        return $data[$field->value];
    }

    /**
     * A field the answer must carry, as text.
     *
     * @param array<array-key, mixed> $data
     */
    private static function text(array $data, NamesAWireField $field): string
    {
        if (! array_key_exists($field->value, $data) || ! is_string($data[$field->value])) {
            throw RestoreIsUnreadable::missing($field);
        }

        return $data[$field->value];
    }

    /**
     * A yes or no the answer must carry.
     *
     * @param array<array-key, mixed> $data
     */
    private static function flag(array $data, NamesAWireField $field): bool
    {
        if (! array_key_exists($field->value, $data) || ! is_bool($data[$field->value])) {
            throw RestoreIsUnreadable::missing($field);
        }

        return $data[$field->value];
    }
}
