<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_key_exists;
use function is_array;
use function is_string;

use Modules\Kernel\Api\ADownloadItWouldInterrupt;
use Modules\Kernel\Api\ALibraryAMoveReaches;
use Modules\Kernel\Api\AnEditFoundInTheFile;
use Modules\Kernel\Api\HowADownloadComes;
use Modules\Kernel\Api\HowFarADownloadIs;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\WhatAChangeAsksFor;
use Modules\Kernel\Api\WhatAChangeComesTo;
use Modules\Kernel\Api\WhatAChangeLeavesAlone;
use Modules\Sdk\Api\Fields\ConfigField;
use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\SettingIsUnreadable;
use Modules\Sdk\Api\WireField;

/**
 * Reads a configuration review's `findings`, and the `consequence` beside it, into what the change comes to.
 */
final readonly class WhatTheReviewFound
{
    /**
     * @param array<mixed> $data   the `config` envelope's payload
     * @param array<mixed> $review its `review`
     */
    public static function in(array $data, array $review): WhatAChangeComesTo
    {
        $comesTo = self::meaning($data);

        if (! array_key_exists(WireField::Findings->value, $review)) {
            return $comesTo;
        }

        $found = $review[WireField::Findings->value];

        if (! is_array($found)) {
            throw SettingIsUnreadable::missing(WireField::Findings);
        }

        return self::edited($found, self::interrupts($found, self::libraries($found, self::keeps($found, self::stops($found, self::asks($found, $comesTo))))));
    }

    /** @param array<mixed> $found */
    private static function asks(array $found, WhatAChangeComesTo $comesTo): WhatAChangeComesTo
    {
        foreach (InTheOrderSent::rows(Required::rows($found, ConfigField::Opens, SettingIsUnreadable::missing(ConfigField::Opens))) as $position => $row) {
            if (! is_array($row)) {
                throw SettingIsUnreadable::finding(ConfigField::Opens, $position);
            }

            $refused = SettingIsUnreadable::finding(ConfigField::Opens, $position);
            $what = Required::text($row, WireField::What, $refused);
            $because = Required::text($row, WireField::Because, $refused);

            $comesTo = $comesTo->asking(! array_key_exists(WireField::Setting->value, $row) || $row[WireField::Setting->value] === null
                ? WhatAChangeAsksFor::toObtain($what, $because)
                : WhatAChangeAsksFor::keptIn($what, $because, Required::text($row, WireField::Setting, $refused)));
        }

        return $comesTo;
    }

    /** @param array<mixed> $found */
    private static function stops(array $found, WhatAChangeComesTo $comesTo): WhatAChangeComesTo
    {
        foreach (self::names($found, ConfigField::Stops) as $name) {
            $comesTo = $comesTo->stopping(ServiceId::called($name));
        }

        return $comesTo;
    }

    /** @param array<mixed> $found */
    private static function keeps(array $found, WhatAChangeComesTo $comesTo): WhatAChangeComesTo
    {
        foreach (self::names($found, WireField::Keeps) as $said) {
            $comesTo = $comesTo->leaving(WhatAChangeLeavesAlone::said($said));
        }

        return $comesTo;
    }

    /**
     * @param  array<mixed> $found
     * @return list<string>
     */
    private static function names(array $found, NamesAWireField $field): array
    {
        $names = [];

        foreach (InTheOrderSent::rows(Required::rows($found, $field, SettingIsUnreadable::missing($field))) as $position => $name) {
            if (! Required::isText($name)) {
                throw SettingIsUnreadable::finding($field, $position);
            }

            $names[] = $name;
        }

        return $names;
    }

    /** @param array<mixed> $found */
    private static function libraries(array $found, WhatAChangeComesTo $comesTo): WhatAChangeComesTo
    {
        foreach (InTheOrderSent::rows(Required::rows($found, ConfigField::Library, SettingIsUnreadable::missing(ConfigField::Library))) as $position => $row) {
            if (! is_array($row)) {
                throw SettingIsUnreadable::finding(ConfigField::Library, $position);
            }

            $refused = SettingIsUnreadable::finding(ConfigField::Library, $position);
            $comesTo = $comesTo->reaching(ALibraryAMoveReaches::of(
                Required::text($row, WireField::Service, $refused),
                Required::text($row, WireField::Path, $refused),
                Required::text($row, WireField::Because, $refused),
                Required::flag($row, WireField::Carried, $refused),
            ));
        }

        return $comesTo;
    }

    /** @param array<mixed> $found */
    private static function interrupts(array $found, WhatAChangeComesTo $comesTo): WhatAChangeComesTo
    {
        foreach (InTheOrderSent::rows(Required::rows($found, ConfigField::Active, SettingIsUnreadable::missing(ConfigField::Active))) as $position => $row) {
            if (! is_array($row)) {
                throw SettingIsUnreadable::finding(ConfigField::Active, $position);
            }

            $refused = SettingIsUnreadable::finding(ConfigField::Active, $position);

            $comesTo = $comesTo->interrupting(ADownloadItWouldInterrupt::of(
                Required::text($row, WireField::Name, $refused),
                HowADownloadComes::named(Required::text($row, ConfigField::Protocol, $refused)),
                HowFarADownloadIs::at(Required::number($row, WireField::Progress, $refused), $refused)->share,
            ));
        }

        return $comesTo;
    }

    /** @param array<mixed> $found */
    private static function edited(array $found, WhatAChangeComesTo $comesTo): WhatAChangeComesTo
    {
        if (! array_key_exists(ConfigField::Edited->value, $found) || $found[ConfigField::Edited->value] === null) {
            return $comesTo;
        }

        $edit = $found[ConfigField::Edited->value];

        if (! is_array($edit)) {
            throw SettingIsUnreadable::missing(ConfigField::Edited);
        }

        $wrote = self::held($edit, ConfigField::Wrote);
        $now = self::held($edit, WireField::Found);

        return $comesTo->withTheEdit(Required::flag($edit, WireField::Secret, SettingIsUnreadable::missing(ConfigField::Edited))
            ? AnEditFoundInTheFile::withheld($wrote, $now)
            : AnEditFoundInTheFile::shown($wrote, $now));
    }

    /**
     * One side of a hand-edit as the stack shows it, which is empty where the setting is.
     *
     * @param array<mixed> $edit
     */
    private static function held(array $edit, NamesAWireField $field): string
    {
        if (! array_key_exists($field->value, $edit) || ! is_string($edit[$field->value])) {
            throw SettingIsUnreadable::missing(ConfigField::Edited);
        }

        return $edit[$field->value];
    }

    /** @param array<mixed> $data */
    private static function meaning(array $data): WhatAChangeComesTo
    {
        if (! array_key_exists(WireField::Consequence->value, $data) || $data[WireField::Consequence->value] === null) {
            return WhatAChangeComesTo::nothing();
        }

        return WhatAChangeComesTo::meaning(Required::text($data, WireField::Consequence, SettingIsUnreadable::missing(WireField::Consequence)));
    }
}
