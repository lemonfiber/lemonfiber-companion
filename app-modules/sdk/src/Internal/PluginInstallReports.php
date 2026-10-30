<?php

declare(strict_types=1);

namespace Modules\Sdk\Internal;

use function array_key_exists;
use function is_array;
use function is_bool;

use Modules\Kernel\Api\ACapabilityLeftContested;
use Modules\Kernel\Api\APluginChange;
use Modules\Kernel\Api\APluginId;
use Modules\Kernel\Api\APluginInstall;
use Modules\Kernel\Api\APrivilegedShape;
use Modules\Kernel\Api\ARunPutBack;
use Modules\Kernel\Api\ASettingItOverrides;
use Modules\Kernel\Api\AShapeTaken;
use Modules\Kernel\Api\PluginLines;
use Modules\Kernel\Api\TheContestsLeft;
use Modules\Kernel\Api\TheGrantsAsked;
use Modules\Kernel\Api\ThePluginChanges;
use Modules\Kernel\Api\TheSettingsItOverrides;
use Modules\Kernel\Api\TheShapesTaken;
use Modules\Kernel\Api\TheUpstreamsWritten;
use Modules\Kernel\Api\WhatAChangePuts;
use Modules\Kernel\Api\WhatAnInstallChanges;
use Modules\Kernel\Api\WhatTheChecksMade;
use Modules\Sdk\Api\Fields\PluginsField;
use Modules\Sdk\Api\NamesAWireField;
use Modules\Sdk\Api\PluginsAreUnreadable;
use Modules\Sdk\Api\WireField;

/**
 * The install a `plugins` answer is about: what it would do, or did, and how it ended.
 *
 * Apart from {@see \Modules\Sdk\Api\PluginInstalls} for its size, and written the same way.
 *
 * **What a proof came to is read by {@see WhatAProofsVerdictWas}**, where a
 * proof with no answer was not asked and is never read as passed. **What
 * putting an install back came to is the rollback's own report**, read by
 * {@see TheReversal} rather than a second time here. **The stack's checks
 * are read by the title each has now**: what the install broke, and what
 * nothing could be concluded about, which is said and never acted on.
 */
final readonly class PluginInstallReports
{
    /**
     * The install, as the stack gave its account.
     *
     * @param array<mixed> $install
     */
    public static function in(array $install): APluginInstall
    {
        if (! array_key_exists(WireField::Would->value, $install) || ! is_array($install[WireField::Would->value])) {
            throw PluginsAreUnreadable::missing(WireField::Would);
        }

        if (! array_key_exists(WireField::Recorded->value, $install) || ! is_bool($install[WireField::Recorded->value])) {
            throw PluginsAreUnreadable::missing(WireField::Recorded);
        }

        $would = PluginRecords::of($install[WireField::Would->value], static fn(NamesAWireField $field): PluginsAreUnreadable => PluginsAreUnreadable::within(WireField::Would, $field));
        $changing = WhatAnInstallChanges::these(
            ThePluginChanges::these(...self::changes($install)),
            TheContestsLeft::these(...self::contests($install)),
            TheSettingsItOverrides::these(...self::overrides($install)),
            TheShapesTaken::these(...self::taking($install)),
            TheGrantsAsked::these(...GrantRows::askedBy($install, APluginId::called($would->id()))),
            TheUpstreamsWritten::these(...SharingRows::in($install)),
            TheWaitingRead::in($install, PluginsAreUnreadable::missing(WireField::Waiting)),
        );
        $proofs = WhatAProofsVerdictWas::proofs($install);
        $checks = self::checks($install);

        if (array_key_exists(WireField::Reversed->value, $install) && $install[WireField::Reversed->value] !== null) {
            return APluginInstall::putBack($would, $changing, $proofs, $checks, self::putBack($install), self::secured($install));
        }

        return APluginInstall::reported($would, recorded: $install[WireField::Recorded->value], changing: $changing, proofs: $proofs, checks: $checks, secured: self::secured($install));
    }

    /**
     * What asking the egress guard to hold its control credential came to, or blank where the stack said nothing of it.
     *
     * @param array<mixed> $install
     */
    private static function secured(array $install): string
    {
        return Optional::text($install, PluginsField::Secured, PluginsAreUnreadable::missing(PluginsField::Secured));
    }

    /**
     * Every change the install makes, in its order.
     *
     * @param  array<mixed>        $install
     * @return list<APluginChange>
     */
    private static function changes(array $install): array
    {
        $found = [];

        foreach (InTheOrderSent::rows(self::rows($install, WireField::Changes)) as $position => $row) {
            if (! is_array($row)) {
                throw PluginsAreUnreadable::entry(WireField::Changes, WireField::Path, $position);
            }

            $puts = self::text($row, WireField::Changes, PluginsField::Puts, $position);

            $found[] = APluginChange::at(
                self::text($row, WireField::Changes, WireField::Path, $position),
                WhatAChangePuts::tryFrom($puts) ?? throw PluginsAreUnreadable::said(PluginsField::Puts, $puts),
            );
        }

        return $found;
    }

    /**
     * Every ask the install would leave contested.
     *
     * @param  array<mixed>                   $install
     * @return list<ACapabilityLeftContested>
     */
    private static function contests(array $install): array
    {
        $found = [];

        foreach (InTheOrderSent::rows(self::rows($install, PluginsField::Contests)) as $position => $row) {
            if (! is_array($row)) {
                throw PluginsAreUnreadable::entry(PluginsField::Contests, WireField::Capability, $position);
            }

            $found[] = ACapabilityLeftContested::over(
                self::text($row, PluginsField::Contests, WireField::Capability, $position),
                self::text($row, PluginsField::Contests, WireField::By, $position),
                PluginLines::under(WireField::Claimants->value, ...self::words($row, PluginsField::Contests, WireField::Claimants, $position)),
            );
        }

        return $found;
    }

    /**
     * Every bundled setting the plugin declares it will change.
     *
     * @param  array<mixed>              $install
     * @return list<ASettingItOverrides>
     */
    private static function overrides(array $install): array
    {
        $found = [];

        foreach (InTheOrderSent::rows(self::rows($install, PluginsField::Overrides)) as $position => $row) {
            if (! is_array($row)) {
                throw PluginsAreUnreadable::entry(PluginsField::Overrides, WireField::Setting, $position);
            }

            $found[] = ASettingItOverrides::of(
                self::text($row, PluginsField::Overrides, WireField::Setting, $position),
                self::text($row, PluginsField::Overrides, WireField::Why, $position),
            );
        }

        return $found;
    }

    /**
     * Every service the install would have take a privileged shape, or none where a stack says nothing of it.
     *
     * @param  array<mixed>      $install
     * @return list<AShapeTaken>
     */
    private static function taking(array $install): array
    {
        if (! array_key_exists(PluginsField::Taking->value, $install)) {
            return [];
        }

        $found = [];

        foreach (InTheOrderSent::rows(self::rows($install, PluginsField::Taking)) as $position => $row) {
            if (! is_array($row)) {
                throw PluginsAreUnreadable::entry(PluginsField::Taking, WireField::Service, $position);
            }

            $shape = self::text($row, PluginsField::Taking, WireField::Shape, $position);

            $found[] = AShapeTaken::by(
                self::text($row, PluginsField::Taking, WireField::Service, $position),
                APrivilegedShape::tryFrom($shape) ?? throw PluginsAreUnreadable::said(WireField::Shape, $shape),
                PluginLines::under(WireField::Grants->value, ...self::words($row, PluginsField::Taking, WireField::Grants, $position)),
                PluginLines::under(WireField::Devices->value, ...self::words($row, PluginsField::Taking, WireField::Devices, $position)),
                self::text($row, PluginsField::Taking, WireField::Approval, $position),
            );
        }

        return $found;
    }

    /**
     * What the stack's own checks made of it, or that none were asked.
     *
     * @param array<mixed> $install
     */
    private static function checks(array $install): WhatTheChecksMade
    {
        if (! array_key_exists(PluginsField::Verified->value, $install) || $install[PluginsField::Verified->value] === null) {
            return WhatTheChecksMade::notAsked();
        }

        $verified = $install[PluginsField::Verified->value];

        if (! is_array($verified)) {
            throw PluginsAreUnreadable::missing(PluginsField::Verified);
        }

        return WhatTheChecksMade::of(
            PluginLines::under(PluginsField::Broke->value, ...self::titles($verified, PluginsField::Broke)),
            PluginLines::under(PluginsField::Unsettled->value, ...self::titles($verified, PluginsField::Unsettled)),
        );
    }

    /**
     * Each check in one list, by the title the stack gives it now.
     *
     * @param  array<mixed> $verified
     * @return list<string>
     */
    private static function titles(array $verified, NamesAWireField $list): array
    {
        $found = [];

        foreach (InTheOrderSent::rows(self::rows($verified, $list)) as $position => $row) {
            if (! is_array($row) || ! array_key_exists(WireField::Now->value, $row) || ! is_array($row[WireField::Now->value])) {
                throw PluginsAreUnreadable::entry($list, WireField::Now, $position);
            }

            $found[] = self::text($row[WireField::Now->value], $list, WireField::Title, $position);
        }

        return $found;
    }

    /**
     * What putting it back came to, where the account carries it.
     *
     * @param array<mixed> $install
     */
    private static function putBack(array $install): ARunPutBack
    {
        if (! is_array($install[WireField::Reversed->value])) {
            throw PluginsAreUnreadable::missing(WireField::Reversed);
        }

        return TheReversal::from($install[WireField::Reversed->value]);
    }

    /**
     * Every word in one list a row carries.
     *
     * @param  array<mixed> $row
     * @return list<string>
     */
    private static function words(array $row, NamesAWireField $list, NamesAWireField $field, int $position): array
    {
        if (! array_key_exists($field->value, $row) || ! is_array($row[$field->value])) {
            throw PluginsAreUnreadable::entry($list, $field, $position);
        }

        $found = [];

        foreach ($row[$field->value] as $word) {
            if (! Required::isText($word)) {
                throw PluginsAreUnreadable::entry($list, $field, $position);
            }

            $found[] = $word;
        }

        return $found;
    }

    /**
     * The rows of one list the answer must carry.
     *
     * @param  array<mixed> $data
     * @return array<mixed>
     */
    private static function rows(array $data, NamesAWireField $list): array
    {
        if (! array_key_exists($list->value, $data) || ! is_array($data[$list->value])) {
            throw PluginsAreUnreadable::missing($list);
        }

        return $data[$list->value];
    }

    /**
     * A word a row must carry, as text an operator can be shown.
     *
     * @param array<mixed> $row
     */
    private static function text(array $row, NamesAWireField $list, NamesAWireField $field, int $position): string
    {
        return Required::text($row, $field, PluginsAreUnreadable::entry($list, $field, $position));
    }
}
