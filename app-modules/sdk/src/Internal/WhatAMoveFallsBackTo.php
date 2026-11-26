<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_map;
use function is_array;

use Modules\Kernel\Api\TheServicesReadingIt;
use Modules\Kernel\Api\WhatASettingFallsBackTo;
use Modules\Kernel\Api\WhereADefaultComesFrom;
use Modules\Sdk\Api\Fields\SettingsField;
use Modules\Sdk\Api\SettingsAreUnreadable;
use Modules\Sdk\Api\WireField;

/**
 * The `default` each setting a change moves carries: tagged by where it comes from, with its value, each reader's own, or none.
 */
final readonly class WhatAMoveFallsBackTo
{
    /**
     * What the moved setting falls back to; a move without one is refused, as every stack sends it.
     *
     * @param array<mixed> $move
     */
    public static function in(array $move, string $where): WhatASettingFallsBackTo
    {
        $at = APath::beneath($where, SettingsField::Default->value);
        $default = Required::rows($move, SettingsField::Default, SettingsAreUnreadable::missing($at));
        $of = Required::text($default, WireField::Of, SettingsAreUnreadable::missing(APath::beneath($at, WireField::Of->value)));

        return match (WhereADefaultComesFrom::tryFrom($of)) {
            WhereADefaultComesFrom::TheCore => WhatASettingFallsBackTo::theCores(self::value($default, $at)),
            WhereADefaultComesFrom::Declared => WhatASettingFallsBackTo::declared(self::value($default, $at)),
            WhereADefaultComesFrom::EachReader => WhatASettingFallsBackTo::eachReadersOwn(self::met($default, $at)),
            WhereADefaultComesFrom::Nowhere => WhatASettingFallsBackTo::nothing(),
            null => throw SettingsAreUnreadable::word(APath::beneath($at, WireField::Of->value), $of, ...array_map(static fn(WhereADefaultComesFrom $case): string => $case->value, WhereADefaultComesFrom::cases())),
        };
    }

    /**
     * The one value, which may be empty and may not be left out.
     *
     * @param array<mixed> $default
     */
    private static function value(array $default, string $at): string
    {
        $value = SettingsRows::held($default, WireField::Value, $at);

        return $value->isHeld() ? $value->said() : throw SettingsAreUnreadable::missing(APath::beneath($at, WireField::Value->value));
    }

    /**
     * Each reader whose needs are met, in the stack's order.
     *
     * @param array<mixed> $default
     */
    private static function met(array $default, string $at): TheServicesReadingIt
    {
        $where = APath::beneath($at, SettingsField::Readers->value);
        $met = [];

        foreach (SettingsRows::listed(Required::rows($default, SettingsField::Readers, SettingsAreUnreadable::missing($where)), $where) as $position => $reader) {
            $listed = APath::beneath($where, $position);

            if (! is_array($reader)) {
                throw SettingsAreUnreadable::missing($listed);
            }

            if (Required::flag($reader, SettingsField::Met, SettingsAreUnreadable::missing(APath::beneath($listed, SettingsField::Met->value)))) {
                $met[] = SettingsRows::reader($reader, $listed);
            }
        }

        return TheServicesReadingIt::these(...$met);
    }
}
