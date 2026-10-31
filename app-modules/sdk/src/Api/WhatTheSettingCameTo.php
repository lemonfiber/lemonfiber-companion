<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function is_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\AnOffer;
use Modules\Kernel\Api\ASettingMove;
use Modules\Kernel\Api\Services;
use Modules\Kernel\Api\TheSettingAsChanged;
use Modules\Kernel\Api\TheSettingMoves;
use Modules\Kernel\Api\WhatTheChangeReaches;
use Modules\Kernel\Api\WhatTheSettingRunDid;
use Modules\Sdk\Api\Fields\SettingsField;
use Modules\Sdk\Internal\APath;
use Modules\Sdk\Internal\Optional;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\SettingsRows;
use Modules\Sdk\Internal\TheWaitingRead;

use function trim;

/**
 * The `settings` envelope a run of a settings action answered with: each setting the change moves, what the run did, and every setting after it.
 *
 * Refused whole with {@see SettingsAreUnreadable} where any part cannot be read,
 * an answer with no change among them; a change that moves no setting, or that
 * both gives values and puts settings back, is refused as the kernel refuses it.
 */
final readonly class WhatTheSettingCameTo
{
    /**
     * The report.
     *
     * @param Envelope<mixed> $envelope the `settings` envelope a finished job answered with
     */
    public static function in(Envelope $envelope): TheSettingAsChanged
    {
        $data = SettingsRows::payload($envelope);
        $at = WireField::Change->value;
        $change = Required::rows($data, WireField::Change, SettingsAreUnreadable::missing($at));

        return TheSettingAsChanged::said(
            self::moves($change, APath::beneath($at, WireField::Settings->value)),
            WhatTheChangeReaches::said(
                self::services($change, SettingsField::Regenerates, $at),
                self::services($change, WireField::Restarts, $at),
                self::services($change, SettingsField::Starts, $at),
                TheWaitingRead::in($change, SettingsAreUnreadable::missing(APath::beneath($at, WireField::Waiting->value))),
                trim(Optional::text($change, WireField::Unrestarted, SettingsAreUnreadable::missing(APath::beneath($at, WireField::Unrestarted->value)))),
            ),
            WhatTheSettingRunDid::said(
                Required::flag($data, WireField::Rehearsed, SettingsAreUnreadable::missing(WireField::Rehearsed->value)),
                Required::flag($change, WireField::Applied, SettingsAreUnreadable::missing(APath::beneath($at, WireField::Applied->value))),
            ),
            AnOffer::named(Required::text($change, WireField::Agreement, SettingsAreUnreadable::missing(APath::beneath($at, WireField::Agreement->value)))),
            TheSettingsListed::in($envelope),
        );
    }

    /**
     * Each setting the change moves; none, or a list that both gives values and puts settings back, is refused.
     *
     * @param array<mixed> $change
     */
    private static function moves(array $change, string $where): TheSettingMoves
    {
        $moves = [];

        foreach (SettingsRows::listed(Required::rows($change, WireField::Settings, SettingsAreUnreadable::missing($where)), $where) as $position => $move) {
            $moves[] = self::move($move, APath::beneath($where, $position));
        }

        return TheSettingMoves::these(...$moves);
    }

    private static function move(mixed $move, string $where): ASettingMove
    {
        if (! is_array($move)) {
            throw SettingsAreUnreadable::missing($where);
        }

        return ASettingMove::said(
            Required::text($move, WireField::Setting, SettingsAreUnreadable::missing(APath::beneath($where, WireField::Setting->value))),
            SettingsRows::held($move, WireField::Was, $where),
            SettingsRows::held($move, WireField::Value, $where),
            Optional::text($move, WireField::Consequence, SettingsAreUnreadable::missing(APath::beneath($where, WireField::Consequence->value))),
        );
    }

    /** @param array<mixed> $change */
    private static function services(array $change, NamesAWireField $field, string $at): Services
    {
        $where = APath::beneath($at, $field->value);

        return SettingsRows::services(Required::rows($change, $field, SettingsAreUnreadable::missing($where)), $where);
    }
}
