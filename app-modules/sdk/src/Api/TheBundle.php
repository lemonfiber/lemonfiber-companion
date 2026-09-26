<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_is_list;
use function array_key_exists;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\BundleEnvelope;
use Modules\Kernel\Api\ABundle;
use Modules\Kernel\Api\APieceOfABundle;
use Modules\Kernel\Api\ASettingToReveal;
use Modules\Kernel\Api\Remarks;
use Modules\Kernel\Api\SettingsToReveal;
use Modules\Kernel\Api\ThePiecesOfABundle;
use Modules\Kernel\Api\TheTermsOfABundle;
use Modules\Kernel\Api\WhatFilenamesShow;
use Modules\Kernel\Api\WhenABundleWasTaken;
use Modules\Kernel\Api\WhereABundleIs;
use Modules\Sdk\Api\Fields\BundleField;
use Modules\Sdk\Internal\Wire;

use function trim;

/**
 * The `bundle` envelope, as the support bundle the stack described or wrote.
 *
 * Every part is required and read whole: each file with what it holds, what
 * could not be collected, the terms it was made on and when it was taken. A
 * body is carried exactly as it arrived, blank lines and all, because what the
 * operator reads here is what whoever they hand it to will read.
 */
final readonly class TheBundle
{
    /**
     * The stack's account of one bundle.
     *
     * @param Envelope<mixed> $envelope the `bundle` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): ABundle
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw BundleIsUnreadable::missing(WireField::Data);
        }

        $contents = self::part($data, BundleField::Contents);

        return ABundle::reported(
            self::bytes($data),
            self::where($data),
            self::terms(self::part($contents, BundleField::Terms)),
            ThePiecesOfABundle::of(...self::pieces($contents)),
            Remarks::of(...self::names($contents, WireField::Missing)),
            self::taken(self::part($contents, WireField::Taken)),
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
        return BundleEnvelope::in($envelope)->data;
    }

    /**
     * A part the bundle must carry as a table of its own.
     *
     * @param  array<array-key, mixed> $data
     * @return array<array-key, mixed>
     */
    private static function part(array $data, NamesAWireField $field): array
    {
        if (! array_key_exists($field->value, $data) || ! is_array($data[$field->value])) {
            throw BundleIsUnreadable::missing($field);
        }

        return $data[$field->value];
    }

    /**
     * How large the file is, or would be.
     *
     * @param array<array-key, mixed> $data
     */
    private static function bytes(array $data): int
    {
        if (! array_key_exists(WireField::Bytes->value, $data)) {
            throw BundleIsUnreadable::missing(WireField::Bytes);
        }

        $bytes = $data[WireField::Bytes->value];

        if (! is_int($bytes) || $bytes < 0) {
            throw BundleIsUnreadable::missing(WireField::Bytes);
        }

        return $bytes;
    }

    /**
     * Where it went, where it would go, or that the stack did not say.
     *
     * A path means it was written, whatever else arrived beside it.
     *
     * @param array<array-key, mixed> $data
     */
    private static function where(array $data): WhereABundleIs
    {
        $path = self::place($data, BundleField::Path);

        if ($path !== null) {
            return WhereABundleIs::writtenAt($path);
        }

        $wouldGo = self::place($data, BundleField::WouldGo);

        return $wouldGo === null ? WhereABundleIs::unsaid() : WhereABundleIs::wouldGo($wouldGo);
    }

    /**
     * A place the bundle may name, or nothing where it names none.
     *
     * Absent and `null` are the same answer, which is the contract's. A place
     * that arrived blank, or as something other than text, is refused rather
     * than drawn as a place called nothing.
     *
     * @param array<array-key, mixed> $data
     */
    private static function place(array $data, NamesAWireField $field): ?string
    {
        if (! array_key_exists($field->value, $data) || $data[$field->value] === null) {
            return null;
        }

        $said = $data[$field->value];

        if (! is_string($said) || trim($said) === '') {
            throw BundleIsUnreadable::missing($field);
        }

        return $said;
    }

    /**
     * The terms it was made on, as the bundle states them.
     *
     * @param array<array-key, mixed> $terms
     */
    private static function terms(array $terms): TheTermsOfABundle
    {
        if (! array_key_exists(BundleField::Filenames->value, $terms) || ! is_bool($terms[BundleField::Filenames->value])) {
            throw BundleIsUnreadable::missing(BundleField::Filenames);
        }

        return TheTermsOfABundle::stated(
            self::text($terms, BundleField::Window),
            $terms[BundleField::Filenames->value] ? WhatFilenamesShow::Shown : WhatFilenamesShow::Replaced,
            self::revealed($terms),
        );
    }

    /**
     * The settings it states it shows as they are.
     *
     * @param array<array-key, mixed> $terms
     */
    private static function revealed(array $terms): SettingsToReveal
    {
        $revealed = SettingsToReveal::none();

        foreach (self::names($terms, BundleField::Revealed) as $name) {
            $revealed = $revealed->with(ASettingToReveal::named($name));
        }

        return $revealed;
    }

    /**
     * Every file it holds, refusing any this app cannot show whole.
     *
     * @param  array<array-key, mixed> $contents
     * @return list<APieceOfABundle>
     */
    private static function pieces(array $contents): array
    {
        $found = [];

        foreach (self::listed($contents, BundleField::Pieces) as $position => $piece) {
            if (! is_array($piece)
                || ! array_key_exists(BundleField::Body->value, $piece)
                || ! is_string($piece[BundleField::Body->value])
            ) {
                throw BundleIsUnreadable::entry(BundleField::Pieces, $position);
            }

            $found[] = APieceOfABundle::of(self::entry($piece, WireField::Name, $position), $piece[BundleField::Body->value]);
        }

        return $found;
    }

    /**
     * A list of plain words the bundle carries, none of them blank.
     *
     * @param  array<array-key, mixed> $data
     * @return list<string>
     */
    private static function names(array $data, NamesAWireField $field): array
    {
        $found = [];

        foreach (self::listed($data, $field) as $position => $said) {
            if (! is_string($said) || trim($said) === '') {
                throw BundleIsUnreadable::entry($field, $position);
            }

            $found[] = $said;
        }

        return $found;
    }

    /**
     * A list the bundle must carry, as it arrived.
     *
     * @param  array<array-key, mixed> $data
     * @return list<mixed>
     */
    private static function listed(array $data, NamesAWireField $field): array
    {
        if (! array_key_exists($field->value, $data)) {
            throw BundleIsUnreadable::missing($field);
        }

        $listed = $data[$field->value];

        if (! is_array($listed) || ! array_is_list($listed)) {
            throw BundleIsUnreadable::missing($field);
        }

        return $listed;
    }

    /**
     * When it was taken, and from which versions.
     *
     * @param array<array-key, mixed> $taken
     */
    private static function taken(array $taken): WhenABundleWasTaken
    {
        return WhenABundleWasTaken::at(
            self::text($taken, WireField::At),
            self::text($taken, BundleField::Lemonfiber),
            self::text($taken, BundleField::Stack),
        );
    }

    /**
     * A named field the bundle must carry, as text an operator can be shown.
     *
     * @param array<array-key, mixed> $data
     */
    private static function text(array $data, NamesAWireField $field): string
    {
        if (! array_key_exists($field->value, $data)) {
            throw BundleIsUnreadable::missing($field);
        }

        $said = $data[$field->value];

        if (! is_string($said) || trim($said) === '') {
            throw BundleIsUnreadable::missing($field);
        }

        return $said;
    }

    /**
     * A named field of one file, as text an operator can be shown.
     *
     * @param array<array-key, mixed> $piece
     */
    private static function entry(array $piece, NamesAWireField $field, int $position): string
    {
        if (! array_key_exists($field->value, $piece)) {
            throw BundleIsUnreadable::entry(BundleField::Pieces, $position);
        }

        $said = $piece[$field->value];

        if (! is_string($said) || trim($said) === '') {
            throw BundleIsUnreadable::entry(BundleField::Pieces, $position);
        }

        return $said;
    }
}
