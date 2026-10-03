<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_is_list;
use function array_key_exists;
use function array_map;
use function is_array;
use function is_bool;
use function is_int;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\UninstallEnvelope;
use Modules\Kernel\Api\AnAmountOfRoom;
use Modules\Kernel\Api\AnUninstall;
use Modules\Kernel\Api\HowMuchWasRead;
use Modules\Kernel\Api\NamedOnTheManifest;
use Modules\Kernel\Api\OneThingItReaches;
use Modules\Kernel\Api\SomethingItCannotTake;
use Modules\Kernel\Api\SomethingLeftBehind;
use Modules\Kernel\Api\SomethingNotLemonfibers;
use Modules\Kernel\Api\SomethingStillComing;
use Modules\Kernel\Api\WhatGoesAndWhatStays;
use Modules\Kernel\Api\WhatIsNotLemonfibers;
use Modules\Kernel\Api\WhatIsStillComing;
use Modules\Kernel\Api\WhatItCannotTake;
use Modules\Kernel\Api\WhatItReaches;
use Modules\Kernel\Api\WhatSortItIs;
use Modules\Kernel\Api\WhatTakingItOffComesTo;
use Modules\Kernel\Api\WhatToKnowFirst;
use Modules\Kernel\Api\WhatWasLeftBehind;
use Modules\Kernel\Api\WhereTakingItOffGot;
use Modules\Kernel\Api\WhereTheUninstallStands;
use Modules\Kernel\Api\WhichRemoval;
use Modules\Sdk\Api\Fields\UninstallField;
use Modules\Sdk\Internal\Wire;

use function trim;

/**
 * Reads the `uninstall` envelope into what taking lemonfiber off would come to, or came to.
 *
 * Written the way {@see TheRestore} is: a static fold with no state, refusing
 * anything the kernel would refuse, with {@see UninstallIsUnreadable}. One
 * reader for both answers, because the reading and the removal arrive in the
 * same envelope: what `/api/uninstall` answers with, and what a finished
 * removal answers with.
 *
 * **What the stack left out is not filled in.** A line's size absent is a size
 * nobody could read, a line with no reason to be kept is going, and a volume
 * or a copy the stack said nothing about is nothing — never a guess.
 */
final readonly class Uninstalls
{
    /**
     * The reading, and where the removal got.
     *
     * @param Envelope<mixed> $envelope the `uninstall` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): AnUninstall
    {
        $data = self::data($envelope);

        return AnUninstall::of(
            self::manifest(self::table($data, WireField::Manifest)),
            self::removal(self::table($data, UninstallField::Removal)),
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
            throw UninstallIsUnreadable::missing(WireField::Data);
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
        return UninstallEnvelope::in($envelope)->data;
    }

    /**
     * What removing would come to, for the removal named by the word the stack wrote.
     *
     * @param array<array-key, mixed> $manifest
     */
    private static function manifest(array $manifest): WhatTakingItOffComesTo
    {
        $tier = self::text($manifest, UninstallField::Tier);

        return WhatTakingItOffComesTo::read(
            WhichRemoval::tryFrom($tier)
                ?? throw UninstallIsUnreadable::word(UninstallField::Tier, $tier, ...array_map(static fn(WhichRemoval $case): string => $case->value, WhichRemoval::cases())),
            WhatGoesAndWhatStays::said(
                self::text($manifest, UninstallField::Removes),
                self::text($manifest, UninstallField::Keeps),
            ),
            WhatItReaches::of(...self::items($manifest)),
            self::number($manifest, WireField::Bytes),
            WhatToKnowFirst::said(
                WhatIsNotLemonfibers::of(...self::foreign($manifest)),
                WhatIsStillComing::of(...self::coming($manifest)),
                WhatItCannotTake::of(...self::outside($manifest)),
                volume: self::sentence($manifest, UninstallField::Volume),
                copyFirst: self::sentence($manifest, UninstallField::Backup),
            ),
            self::confidence(self::table($manifest, WireField::Confidence)),
            self::text($manifest, WireField::Agreement),
        );
    }

    /**
     * Every line it reaches, going or kept.
     *
     * @param  array<array-key, mixed> $manifest
     * @return list<OneThingItReaches>
     */
    private static function items(array $manifest): array
    {
        $lines = [];

        foreach (self::rows($manifest, WireField::Items) as $entry) {
            $item = self::row($entry, WireField::Items);
            $sort = self::text($item, UninstallField::Sort);
            $said = [
                self::text($item, WireField::Name),
                WhatSortItIs::tryFrom($sort)
                    ?? throw UninstallIsUnreadable::word(UninstallField::Sort, $sort, ...array_map(static fn(WhatSortItIs $case): string => $case->value, WhatSortItIs::cases())),
                self::text($item, WireField::What),
                self::flag($item, WireField::Secret),
                self::size($item),
            ];
            $kept = self::sentence($item, WireField::Kept);

            $lines[] = $kept === '' ? OneThingItReaches::going(...$said) : OneThingItReaches::kept(...[...$said, $kept]);
        }

        return $lines;
    }

    /**
     * What one line occupies, or nothing where the stack could not say.
     *
     * @param array<array-key, mixed> $item
     */
    private static function size(array $item): AnAmountOfRoom
    {
        if (! array_key_exists(WireField::Bytes->value, $item) || $item[WireField::Bytes->value] === null) {
            return AnAmountOfRoom::unread();
        }

        return AnAmountOfRoom::of(self::number($item, WireField::Bytes), WireField::Bytes->value);
    }

    /**
     * What beneath the data location is not lemonfiber's.
     *
     * @param  array<array-key, mixed> $manifest
     * @return list<SomethingNotLemonfibers>
     */
    private static function foreign(array $manifest): array
    {
        $found = [];

        foreach (self::rows($manifest, UninstallField::Foreign) as $entry) {
            $foreign = self::row($entry, UninstallField::Foreign);
            $found[] = SomethingNotLemonfibers::at(
                self::text($foreign, WireField::At),
                self::number($foreign, UninstallField::Files),
                self::number($foreign, WireField::Bytes),
            );
        }

        return $found;
    }

    /**
     * What is still coming down.
     *
     * @param  array<array-key, mixed> $manifest
     * @return list<SomethingStillComing>
     */
    private static function coming(array $manifest): array
    {
        $coming = [];

        foreach (self::rows($manifest, UninstallField::Coming) as $entry) {
            $download = self::row($entry, UninstallField::Coming);
            $coming[] = SomethingStillComing::named(
                self::text($download, WireField::Name),
                self::number($download, UninstallField::Progress),
            );
        }

        return $coming;
    }

    /**
     * What lemonfiber cannot take, found or not.
     *
     * @param  array<array-key, mixed> $manifest
     * @return list<SomethingItCannotTake>
     */
    private static function outside(array $manifest): array
    {
        $outside = [];

        foreach (self::rows($manifest, UninstallField::Outside) as $entry) {
            $thing = self::row($entry, UninstallField::Outside);
            $said = [
                self::text($thing, WireField::What),
                self::text($thing, WireField::Why),
                self::text($thing, UninstallField::ByHand),
            ];

            $outside[] = self::flag($thing, UninstallField::Found)
                ? SomethingItCannotTake::found(...$said)
                : SomethingItCannotTake::notFound(...$said);
        }

        return $outside;
    }

    /**
     * How much of it was read.
     *
     * @param array<array-key, mixed> $confidence
     */
    private static function confidence(array $confidence): HowMuchWasRead
    {
        $unread = self::names($confidence, WireField::Unread);

        return self::flag($confidence, UninstallField::Complete)
            ? HowMuchWasRead::everything(...$unread)
            : HowMuchWasRead::notEverything(...$unread);
    }

    /**
     * Where the removal got, from the tag the stack wrote and the fields beside it.
     *
     * @param array<array-key, mixed> $removal
     */
    private static function removal(array $removal): WhereTakingItOffGot
    {
        $said = self::text($removal, WireField::State);
        $state = WhereTheUninstallStands::tryFrom($said)
            ?? throw UninstallIsUnreadable::word(WireField::State, $said, ...array_map(static fn(WhereTheUninstallStands $case): string => $case->value, WhereTheUninstallStands::cases()));

        return match ($state) {
            WhereTheUninstallStands::Surveyed => WhereTakingItOffGot::surveyed(),
            WhereTheUninstallStands::Confirmed => WhereTakingItOffGot::rehearsed(),
            WhereTheUninstallStands::Complete => WhereTakingItOffGot::complete(
                NamedOnTheManifest::under(WireField::Gone->value, ...self::names($removal, WireField::Gone)),
                NamedOnTheManifest::under(UninstallField::Credentials->value, ...self::names($removal, UninstallField::Credentials)),
            ),
            WhereTheUninstallStands::Partial => WhereTakingItOffGot::partial(
                NamedOnTheManifest::under(WireField::Gone->value, ...self::names($removal, WireField::Gone)),
                NamedOnTheManifest::under(UninstallField::Credentials->value, ...self::names($removal, UninstallField::Credentials)),
                WhatWasLeftBehind::of(...self::left($removal)),
            ),
        };
    }

    /**
     * What a removal could not take.
     *
     * @param  array<array-key, mixed> $removal
     * @return list<SomethingLeftBehind>
     */
    private static function left(array $removal): array
    {
        $left = [];

        foreach (self::rows($removal, WireField::Left) as $entry) {
            $thing = self::row($entry, WireField::Left);
            $left[] = SomethingLeftBehind::named(
                self::text($thing, WireField::Name),
                self::text($thing, WireField::Why),
                self::text($thing, UninstallField::ByHand),
            );
        }

        return $left;
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
            throw UninstallIsUnreadable::missing($field);
        }

        return $data[$field->value];
    }

    /**
     * A list the answer must carry, each entry still to be checked by {@see row()}.
     *
     * @param  array<array-key, mixed> $data
     * @return list<mixed>
     */
    private static function rows(array $data, NamesAWireField $field): array
    {
        $listed = self::table($data, $field);

        if (! array_is_list($listed)) {
            throw UninstallIsUnreadable::missing($field);
        }

        return $listed;
    }

    /**
     * One entry of a list, which must be a table.
     *
     * @return array<array-key, mixed>
     */
    private static function row(mixed $entry, NamesAWireField $field): array
    {
        if (! is_array($entry)) {
            throw UninstallIsUnreadable::missing($field);
        }

        return $entry;
    }

    /**
     * A list of names or sentences the answer must carry, each text.
     *
     * @param  array<array-key, mixed> $data
     * @return list<string>
     */
    private static function names(array $data, NamesAWireField $field): array
    {
        $names = [];

        foreach (self::table($data, $field) as $name) {
            if (! is_string($name)) {
                throw UninstallIsUnreadable::missing($field);
            }

            $names[] = $name;
        }

        return $names;
    }

    /**
     * A field the answer must carry, as text.
     *
     * @param array<array-key, mixed> $data
     */
    private static function text(array $data, NamesAWireField $field): string
    {
        // A guard rather than `?? null` on the subscript, which `C9` refuses.
        if (! array_key_exists($field->value, $data) || ! is_string($data[$field->value]) || trim($data[$field->value]) === '') {
            throw UninstallIsUnreadable::missing($field);
        }

        return $data[$field->value];
    }

    /**
     * A sentence the answer may carry, and nothing where it is absent or null.
     *
     * @param array<array-key, mixed> $data
     */
    private static function sentence(array $data, NamesAWireField $field): string
    {
        if (! array_key_exists($field->value, $data) || $data[$field->value] === null) {
            return '';
        }

        return self::text($data, $field);
    }

    /**
     * A whole number the answer must carry.
     *
     * @param array<array-key, mixed> $data
     */
    private static function number(array $data, NamesAWireField $field): int
    {
        if (! array_key_exists($field->value, $data) || ! is_int($data[$field->value])) {
            throw UninstallIsUnreadable::missing($field);
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
            throw UninstallIsUnreadable::missing($field);
        }

        return $data[$field->value];
    }
}
