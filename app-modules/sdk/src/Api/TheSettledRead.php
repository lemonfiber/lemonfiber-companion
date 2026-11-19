<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function array_map;
use function is_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\SettledEnvelope;
use Modules\Kernel\Api\HowFarSetupGot;
use Modules\Kernel\Api\TheDataRoot;
use Modules\Kernel\Api\TheServiceUser;
use Modules\Kernel\Api\TheWaysOfDownloading;
use Modules\Kernel\Api\WhatSetupSettled;
use Modules\Kernel\Api\WhereTheServiceUserComesFrom;
use Modules\Sdk\Api\Fields\SettledField;
use Modules\Sdk\Internal\Optional;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\Wire;

/**
 * The `settled` envelope, as what setup settled.
 */
final readonly class TheSettledRead
{
    /** @param array<mixed> $data */
    private static function setup(array $data): HowFarSetupGot
    {
        $said = Required::text($data, SettledField::Setup, SettledIsUnreadable::missing(SettledField::Setup));

        return HowFarSetupGot::tryFrom($said) ?? throw SettledIsUnreadable::word(SettledField::Setup, $said, ...array_map(static fn(HowFarSetupGot $one): string => $one->value, HowFarSetupGot::cases()));
    }
    /**
     * How far setup got, where the library is kept, how it downloads and who its services run as.
     *
     * @param Envelope<mixed> $envelope the `settled` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): WhatSetupSettled
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw SettledIsUnreadable::missing(WireField::Data);
        }

        $root = Optional::text($data, SettledField::DataRoot, SettledIsUnreadable::missing(SettledField::DataRoot));
        $protocols = self::table($data, SettledField::Protocols);
        $user = self::table($data, SettledField::ServiceUser);

        return WhatSetupSettled::said(
            self::setup($data),
            $root === '' ? TheDataRoot::notChosen() : TheDataRoot::at($root),
            TheWaysOfDownloading::of(
                Required::flag($protocols, SettledField::Usenet, SettledIsUnreadable::missing(SettledField::Usenet)),
                Required::flag($protocols, SettledField::Torrent, SettledIsUnreadable::missing(SettledField::Torrent)),
            ),
            TheServiceUser::runsAs(
                Required::count($user, SettledField::Uid, SettledIsUnreadable::missing(SettledField::Uid)),
                Required::count($user, SettledField::Gid, SettledIsUnreadable::missing(SettledField::Gid)),
                self::from($user),
            ),
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
        return SettledEnvelope::in($envelope)->data;
    }

    /** @param array<mixed> $user */
    private static function from(array $user): WhereTheServiceUserComesFrom
    {
        $said = Required::text($user, WireField::From, SettledIsUnreadable::missing(WireField::From));

        return WhereTheServiceUserComesFrom::tryFrom($said) ?? throw SettledIsUnreadable::word(WireField::From, $said, ...array_map(static fn(WhereTheServiceUserComesFrom $one): string => $one->value, WhereTheServiceUserComesFrom::cases()));
    }

    /**
     * An object a field must hold.
     *
     * @param  array<mixed> $data
     * @return array<mixed>
     */
    private static function table(array $data, NamesAWireField $field): array
    {
        return array_key_exists($field->value, $data) && is_array($data[$field->value]) ? $data[$field->value] : throw SettledIsUnreadable::missing($field);
    }
}
