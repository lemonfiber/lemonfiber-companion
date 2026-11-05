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
use Modules\Kernel\Api\WhatAChangeAsksFor;
use Modules\Kernel\Api\WhatAChangeComesTo;
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
        if (! array_key_exists(WireField::Findings->value, $review)) {
            return self::meaning($data, WhatAChangeComesTo::nothing());
        }

        $found = $review[WireField::Findings->value];

        if (! is_array($found)) {
            throw SettingIsUnreadable::missing(WireField::Findings);
        }

        return self::meaning($data, self::edited($found, WhatAChangeComesTo::found(
            self::asks($found),
            self::names($found, ConfigField::Stops),
            self::names($found, WireField::Keeps),
            self::libraries($found),
            self::interrupts($found),
        )));
    }

    /**
     * @param  array<mixed> $found
     * @return list<WhatAChangeAsksFor>
     */
    private static function asks(array $found): array
    {
        $asks = [];

        foreach (InTheOrderSent::rows(Required::rows($found, ConfigField::Opens, SettingIsUnreadable::missing(ConfigField::Opens))) as $position => $row) {
            if (! is_array($row)) {
                throw SettingIsUnreadable::finding(ConfigField::Opens, $position);
            }

            $what = Required::text($row, WireField::What, SettingIsUnreadable::finding(ConfigField::Opens, $position));
            $because = Required::text($row, WireField::Because, SettingIsUnreadable::finding(ConfigField::Opens, $position));

            if (! array_key_exists(WireField::Setting->value, $row) || $row[WireField::Setting->value] === null) {
                $asks[] = WhatAChangeAsksFor::toObtain($what, $because);

                continue;
            }

            $asks[] = WhatAChangeAsksFor::keptIn($what, $because, Required::text($row, WireField::Setting, SettingIsUnreadable::finding(ConfigField::Opens, $position)));
        }

        return $asks;
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

    /**
     * @param  array<mixed> $found
     * @return list<ALibraryAMoveReaches>
     */
    private static function libraries(array $found): array
    {
        $libraries = [];

        foreach (InTheOrderSent::rows(Required::rows($found, ConfigField::Library, SettingIsUnreadable::missing(ConfigField::Library))) as $position => $row) {
            if (! is_array($row)) {
                throw SettingIsUnreadable::finding(ConfigField::Library, $position);
            }

            $refused = SettingIsUnreadable::finding(ConfigField::Library, $position);
            $libraries[] = ALibraryAMoveReaches::of(
                Required::text($row, WireField::Service, $refused),
                Required::text($row, WireField::Path, $refused),
                Required::text($row, WireField::Because, $refused),
                Required::flag($row, WireField::Carried, $refused),
            );
        }

        return $libraries;
    }

    /**
     * @param  array<mixed> $found
     * @return list<ADownloadItWouldInterrupt>
     */
    private static function interrupts(array $found): array
    {
        $interrupts = [];

        foreach (InTheOrderSent::rows(Required::rows($found, ConfigField::Active, SettingIsUnreadable::missing(ConfigField::Active))) as $position => $row) {
            if (! is_array($row)) {
                throw SettingIsUnreadable::finding(ConfigField::Active, $position);
            }

            $refused = SettingIsUnreadable::finding(ConfigField::Active, $position);
            $progress = Required::number($row, WireField::Progress, $refused);

            if (! HowFarADownloadIs::couldBe($progress)) {
                throw $refused;
            }

            $interrupts[] = ADownloadItWouldInterrupt::of(
                Required::text($row, WireField::Name, $refused),
                HowADownloadComes::named(Required::text($row, ConfigField::Protocol, $refused)),
                $progress,
            );
        }

        return $interrupts;
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
    private static function meaning(array $data, WhatAChangeComesTo $comesTo): WhatAChangeComesTo
    {
        if (! array_key_exists(WireField::Consequence->value, $data) || $data[WireField::Consequence->value] === null) {
            return $comesTo;
        }

        return $comesTo->meaning(Required::text($data, WireField::Consequence, SettingIsUnreadable::missing(WireField::Consequence)));
    }
}
