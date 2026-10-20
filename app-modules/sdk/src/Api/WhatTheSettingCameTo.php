<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\AnOffer;
use Modules\Kernel\Api\ASettingChange;
use Modules\Kernel\Api\TheSettingAsChanged;
use Modules\Kernel\Api\WhatTheChangeReaches;
use Modules\Kernel\Api\WhatTheSettingRunDid;
use Modules\Sdk\Api\Fields\SettingsField;
use Modules\Sdk\Internal\APath;
use Modules\Sdk\Internal\Optional;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\SettingsRows;

use function trim;

/**
 * The `settings` envelope a run of a setting's action answered with: the change, what the run did, and every setting after it.
 *
 * Refused whole with {@see SettingsAreUnreadable} where any part cannot be read,
 * an answer with no change among them.
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
        $setting = Required::text($change, WireField::Setting, SettingsAreUnreadable::missing(APath::beneath($at, WireField::Setting->value)));
        $value = SettingsRows::held($change, WireField::Value, $at);

        return TheSettingAsChanged::said(
            $value->isHeld() ? ASettingChange::toHold($setting, $value->said()) : ASettingChange::backToDefault($setting),
            SettingsRows::held($change, WireField::Was, $at),
            WhatTheChangeReaches::said(SettingsRows::services(Required::rows($change, SettingsField::Regenerates, SettingsAreUnreadable::missing(APath::beneath($at, SettingsField::Regenerates->value))), APath::beneath($at, SettingsField::Regenerates->value)), SettingsRows::services(Required::rows($change, SettingsField::Restarts, SettingsAreUnreadable::missing(APath::beneath($at, SettingsField::Restarts->value))), APath::beneath($at, SettingsField::Restarts->value)), trim(Optional::text($change, WireField::Consequence, SettingsAreUnreadable::missing(APath::beneath($at, WireField::Consequence->value)))), trim(Optional::text($change, SettingsField::Unrestarted, SettingsAreUnreadable::missing(APath::beneath($at, SettingsField::Unrestarted->value))))),
            WhatTheSettingRunDid::said(
                Required::flag($data, WireField::Rehearsed, SettingsAreUnreadable::missing(WireField::Rehearsed->value)),
                Required::flag($change, WireField::Applied, SettingsAreUnreadable::missing(APath::beneath($at, WireField::Applied->value))),
            ),
            AnOffer::named(Required::text($change, WireField::Agreement, SettingsAreUnreadable::missing(APath::beneath($at, WireField::Agreement->value)))),
            TheSettingsListed::in($envelope),
        );
    }
}
