<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_map;
use function is_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\StoredEnvelope;
use Modules\Kernel\Api\APathLeft;
use Modules\Kernel\Api\TheForgetting;
use Modules\Kernel\Api\ThePlacesGone;
use Modules\Kernel\Api\WhereForgettingGot;
use Modules\Kernel\Api\WhereTheForgettingStands;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\Wire;

/**
 * The `stored` envelope a `forget` run answers with, as what it lists and how far removing it went.
 */
final readonly class WhatForgettingCameTo
{
    /** @param Envelope<mixed> $envelope the envelope the finished work redeemed */
    public static function in(Envelope $envelope): TheForgetting
    {
        $data = self::payload($envelope);
        $removal = Required::rows($data, WireField::Removal, StoredIsUnreadable::missing(WireField::Removal));
        $said = Required::text($removal, WireField::State, StoredIsUnreadable::missing(WireField::State));
        $state = WhereTheForgettingStands::tryFrom($said)
            ?? throw StoredIsUnreadable::removal($said, ...array_map(static fn(WhereTheForgettingStands $case): string => $case->value, WhereTheForgettingStands::cases()));

        return TheForgetting::said(WhatIsStored::in($envelope), match ($state) {
            WhereTheForgettingStands::NotAsked => WhereForgettingGot::notAsked(),
            WhereTheForgettingStands::Unconfirmed => WhereForgettingGot::unconfirmed(),
            WhereTheForgettingStands::Done => WhereForgettingGot::done(self::gone($removal), ...self::left($removal)),
        });
    }

    /**
     * @param  Envelope<mixed> $envelope
     * @return array<mixed>
     */
    private static function payload(Envelope $envelope): array
    {
        $data = self::data(Wire::checked($envelope));

        if (! is_array($data)) {
            throw StoredIsUnreadable::missing(WireField::Data);
        }

        return $data;
    }

    /** @param Envelope<mixed> $envelope */
    private static function data(Envelope $envelope): mixed
    {
        return StoredEnvelope::in($envelope)->data;
    }

    /** @param array<mixed> $removal */
    private static function gone(array $removal): ThePlacesGone
    {
        $gone = [];

        foreach (Required::rows($removal, WireField::Gone, StoredIsUnreadable::missing(WireField::Gone)) as $position => $place) {
            if (! Required::isText($place)) {
                throw StoredIsUnreadable::row(WireField::Gone, (int) $position);
            }

            $gone[] = $place;
        }

        return ThePlacesGone::of(...$gone);
    }

    /**
     * @param  array<mixed>    $removal
     * @return list<APathLeft>
     */
    private static function left(array $removal): array
    {
        $left = [];

        foreach (Required::rows($removal, WireField::Left, StoredIsUnreadable::missing(WireField::Left)) as $position => $row) {
            if (! is_array($row)) {
                throw StoredIsUnreadable::row(WireField::Left, (int) $position);
            }

            $left[] = APathLeft::at(
                Required::text($row, WireField::At, StoredIsUnreadable::said(WireField::Left, WireField::At, (int) $position)),
                Required::text($row, WireField::Why, StoredIsUnreadable::said(WireField::Left, WireField::Why, (int) $position)),
            );
        }

        return $left;
    }
}
