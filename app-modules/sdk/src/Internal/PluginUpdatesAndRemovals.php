<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_key_exists;
use function is_array;
use function is_bool;
use function is_string;

use Modules\Kernel\Api\ACapabilityLeftUnfilled;
use Modules\Kernel\Api\AnUpdate;
use Modules\Kernel\Api\APluginRemoval;
use Modules\Kernel\Api\PluginLines;
use Modules\Kernel\Api\TheCapabilitiesLeftUnfilled;
use Modules\Kernel\Api\TheVersionsItMovesBetween;
use Modules\Kernel\Api\WhatPuttingTheOldVersionBackCameTo;
use Modules\Sdk\Api\Fields\PluginsField;
use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\PluginsAreUnreadable;
use Modules\Sdk\Api\WireField;

use function trim;

/**
 * An update or a removal a `plugins` answer is about: what it would do, or did.
 *
 * Apart from {@see \Modules\Sdk\Api\PluginInstalls} for its size, and written
 * the same way. **An update carries an install's whole account**, read by
 * {@see PluginInstallReports}, and **each carries the rollback's own report**
 * of what putting the installed version back came to, read by
 * {@see TheReversal}: a reading is told by that report being a rehearsal.
 */
final readonly class PluginUpdatesAndRemovals
{
    /**
     * The update, as the stack gave its account.
     *
     * @param array<mixed> $update
     */
    public static function update(array $update): AnUpdate
    {
        if (! array_key_exists(PluginsField::Install->value, $update) || ! is_array($update[PluginsField::Install->value])) {
            throw PluginsAreUnreadable::missing(PluginsField::Install);
        }

        return AnUpdate::reported(
            self::text($update, PluginsField::Plugin),
            TheVersionsItMovesBetween::of(self::text($update, WireField::From), self::text($update, WireField::To)),
            self::words($update, PluginsField::Interrupts),
            PluginInstallReports::in($update[PluginsField::Install->value]),
            TheReversal::from(self::table($update, PluginsField::WentBack)),
            self::optional($update, WireField::Stopped),
            self::restored($update),
        );
    }

    /**
     * The removal, as the stack gave its account.
     *
     * @param array<mixed> $removal
     */
    public static function removal(array $removal): APluginRemoval
    {
        if (! array_key_exists(WireField::Removed->value, $removal) || ! is_bool($removal[WireField::Removed->value])) {
            throw PluginsAreUnreadable::missing(WireField::Removed);
        }

        return APluginRemoval::reported(
            self::text($removal, PluginsField::Plugin),
            self::words($removal, PluginsField::Interrupts),
            TheCapabilitiesLeftUnfilled::these(...self::leaves($removal)),
            $removal[WireField::Removed->value],
            TheReversal::from(self::table($removal, PluginsField::WentBack)),
        );
    }

    /**
     * What putting the version it replaced back came to, or that nothing was.
     *
     * @param array<mixed> $update
     */
    private static function restored(array $update): WhatPuttingTheOldVersionBackCameTo
    {
        if (! array_key_exists(PluginsField::Restored->value, $update) || $update[PluginsField::Restored->value] === null) {
            return WhatPuttingTheOldVersionBackCameTo::notNeeded();
        }

        $restored = $update[PluginsField::Restored->value];

        if (! is_array($restored)
            || ! array_key_exists(PluginsField::Placed->value, $restored) || ! is_bool($restored[PluginsField::Placed->value])
            || ! array_key_exists(WireField::Running->value, $restored) || ! is_bool($restored[WireField::Running->value])) {
            throw PluginsAreUnreadable::missing(PluginsField::Restored);
        }

        return WhatPuttingTheOldVersionBackCameTo::of(
            self::text($restored, WireField::Version),
            placed: $restored[PluginsField::Placed->value],
            running: $restored[WireField::Running->value],
        );
    }

    /**
     * Every capability nothing would fill afterwards, with what fills it now.
     *
     * @param  array<mixed>                  $removal
     * @return list<ACapabilityLeftUnfilled>
     */
    private static function leaves(array $removal): array
    {
        $found = [];
        $position = 0;

        foreach (self::table($removal, PluginsField::Leaves) as $row) {
            if (! is_array($row)) {
                throw PluginsAreUnreadable::entry(PluginsField::Leaves, WireField::Capability, $position);
            }

            $found[] = ACapabilityLeftUnfilled::of(self::text($row, WireField::Capability), self::text($row, PluginsField::FilledBy));
            $position++;
        }

        return $found;
    }

    /**
     * Every word in one list.
     *
     * @param array<mixed> $data
     */
    private static function words(array $data, NamesAWireField $field): PluginLines
    {
        $found = [];

        foreach (self::table($data, $field) as $word) {
            if (! is_string($word) || trim($word) === '') {
                throw PluginsAreUnreadable::missing($field);
            }

            $found[] = $word;
        }

        return PluginLines::under($field->value, ...$found);
    }

    /**
     * A table or a list the answer must carry.
     *
     * @param  array<mixed> $data
     * @return array<mixed>
     */
    private static function table(array $data, NamesAWireField $field): array
    {
        if (! array_key_exists($field->value, $data) || ! is_array($data[$field->value])) {
            throw PluginsAreUnreadable::missing($field);
        }

        return $data[$field->value];
    }

    /**
     * A word the answer must carry.
     *
     * @param array<mixed> $data
     */
    private static function text(array $data, NamesAWireField $field): string
    {
        if (! array_key_exists($field->value, $data) || ! is_string($data[$field->value]) || trim($data[$field->value]) === '') {
            throw PluginsAreUnreadable::missing($field);
        }

        return $data[$field->value];
    }

    /**
     * A word the answer may leave out or send as null, as empty where it did.
     *
     * @param array<mixed> $data
     */
    private static function optional(array $data, NamesAWireField $field): string
    {
        if (! array_key_exists($field->value, $data) || $data[$field->value] === null) {
            return '';
        }

        if (! is_string($data[$field->value])) {
            throw PluginsAreUnreadable::missing($field);
        }

        return $data[$field->value];
    }
}
