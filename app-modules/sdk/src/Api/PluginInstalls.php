<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\PluginsEnvelope;
use Modules\Kernel\Api\AnAnswerOutOfContract;
use Modules\Kernel\Api\APlugin;
use Modules\Kernel\Api\APluginReproof;
use Modules\Kernel\Api\ASourceAsked;
use Modules\Kernel\Api\HowItsSourceStands;
use Modules\Kernel\Api\TheAnswersOutOfContract;
use Modules\Kernel\Api\TheInstalledPlugins;
use Modules\Kernel\Api\ThePlugins;
use Modules\Kernel\Api\ThePluginSources;
use Modules\Kernel\Api\WhereItsSourceStands;
use Modules\Sdk\Api\Fields\PluginsField;
use Modules\Sdk\Internal\InTheOrderSent;
use Modules\Sdk\Internal\PluginInstallReports;
use Modules\Sdk\Internal\PluginRecipeRuns;
use Modules\Sdk\Internal\PluginRecords;
use Modules\Sdk\Internal\PluginUpdatesAndRemovals;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\WhatAProofsVerdictWas;
use Modules\Sdk\Internal\Wire;

/**
 * The `plugins` envelope, as what a stack says of its plugins.
 *
 * Written the way {@see TheRunPutBack} is: a static fold with no state,
 * refusing rather than salvaging. One reading for the listing, a rehearsal and
 * an install, because the stack answers all three with this one envelope.
 *
 * The install an answer is about is read by {@see PluginInstallReports}, an
 * update or a removal by {@see PluginUpdatesAndRemovals}, and each plugin,
 * listed or about to be installed, by {@see PluginRecords}.
 */
final readonly class PluginInstalls
{
    /**
     * What the stack said of its plugins.
     *
     * @param Envelope<mixed> $envelope the `plugins` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): ThePlugins
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw PluginsAreUnreadable::missing(WireField::Data);
        }

        $installed = TheInstalledPlugins::these(...self::installed($data));
        $sources = self::sources($data);
        $outOfContract = self::outOfContract($data);

        $agreement = self::agreement($data);

        return match (true) {
            self::carries($data, PluginsField::Install) => ThePlugins::aboutAnInstall($installed, $sources, $agreement, PluginInstallReports::in(self::table($data, PluginsField::Install))),
            self::carries($data, PluginsField::Update) => ThePlugins::aboutAnUpdate($installed, $sources, $agreement, PluginUpdatesAndRemovals::update(self::table($data, PluginsField::Update))),
            self::carries($data, WireField::Removal) => ThePlugins::aboutAPluginRemoval($installed, $sources, $agreement, PluginUpdatesAndRemovals::removal(self::table($data, WireField::Removal))),
            self::carries($data, PluginsField::Proof) => ThePlugins::aboutAReproof($installed, $sources, $agreement, self::reproof(self::table($data, PluginsField::Proof))),
            self::carries($data, PluginsField::Demand) => ThePlugins::aboutARecipeRun($installed, $sources, $agreement, PluginRecipeRuns::in(self::table($data, PluginsField::Demand))),
            default => ThePlugins::listed($installed, $sources, $outOfContract),
        };
    }

    /**
     * Whether the answer carries this account at all: absent and null are the same.
     *
     * @param array<mixed> $data
     */
    private static function carries(array $data, NamesAWireField $account): bool
    {
        return array_key_exists($account->value, $data) && $data[$account->value] !== null;
    }

    /**
     * One account the answer carries, which has to be a table.
     *
     * @param  array<mixed> $data
     * @return array<mixed>
     */
    private static function table(array $data, NamesAWireField $account): array
    {
        if (! is_array($data[$account->value])) {
            throw PluginsAreUnreadable::missing($account);
        }

        return $data[$account->value];
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
        return PluginsEnvelope::in($envelope)->data;
    }

    /**
     * Every plugin the record holds.
     *
     * @param  array<mixed>  $data
     * @return list<APlugin>
     */
    private static function installed(array $data): array
    {
        $found = [];

        foreach (InTheOrderSent::rows(self::rows($data, WireField::Installed)) as $position => $row) {
            if (! is_array($row)) {
                throw PluginsAreUnreadable::entry(WireField::Installed, WireField::Plugin, $position);
            }

            $found[] = PluginRecords::of($row, WireField::Installed, $position);
        }

        return $found;
    }

    /**
     * How each installed plugin's source stands, by the plugin's id; none where the stack asked none.
     *
     * @param array<mixed> $data
     */
    private static function sources(array $data): ThePluginSources
    {
        if (! array_key_exists(PluginsField::Sources->value, $data)) {
            return ThePluginSources::these();
        }

        $found = [];

        foreach (InTheOrderSent::rows(self::rows($data, PluginsField::Sources)) as $position => $row) {
            if (! is_array($row) || ! array_key_exists(WireField::Standing->value, $row) || ! is_array($row[WireField::Standing->value])) {
                throw PluginsAreUnreadable::entry(PluginsField::Sources, WireField::Standing, $position);
            }

            $found[] = ASourceAsked::of(self::text($row, PluginsField::Sources, WireField::Plugin, $position), self::standing($row[WireField::Standing->value], $position));
        }

        return ThePluginSources::these(...$found);
    }

    /**
     * Every answer the installed plugins' adapters gave outside their contracts; none where the listing carries none.
     *
     * @param array<mixed> $data
     */
    private static function outOfContract(array $data): TheAnswersOutOfContract
    {
        if (! array_key_exists(PluginsField::Nonconforming->value, $data)) {
            return TheAnswersOutOfContract::these();
        }

        return self::answersIn($data, PluginsField::Nonconforming);
    }

    /**
     * Proving a plugin again: each proof, the answers kept against it, and whether its adapters were asked and cleared them.
     *
     * @param array<mixed> $proof
     */
    private static function reproof(array $proof): APluginReproof
    {
        return APluginReproof::reported(
            Required::text($proof, WireField::Plugin, PluginsAreUnreadable::missing(WireField::Plugin)),
            WhatAProofsVerdictWas::proofs($proof),
            self::answersIn($proof, WireField::Kept),
            Required::flag($proof, WireField::Asked, PluginsAreUnreadable::missing(WireField::Asked)),
            Required::flag($proof, PluginsField::Cleared, PluginsAreUnreadable::missing(PluginsField::Cleared)),
        );
    }

    /**
     * Each answer outside a contract listed under that field, refused rather than dropped.
     *
     * @param array<mixed> $table
     */
    private static function answersIn(array $table, NamesAWireField $list): TheAnswersOutOfContract
    {
        $found = [];

        foreach (InTheOrderSent::rows(self::rows($table, $list)) as $position => $row) {
            if (! is_array($row)) {
                throw PluginsAreUnreadable::entry($list, WireField::Plugin, $position);
            }

            $found[] = AnAnswerOutOfContract::of(
                self::text($row, $list, WireField::Plugin, $position),
                self::text($row, $list, WireField::Capability, $position),
                self::text($row, $list, WireField::Operation, $position),
                self::text($row, $list, WireField::Why, $position),
            );
        }

        return TheAnswersOutOfContract::these(...$found);
    }

    /**
     * How one source stands, by its own word.
     *
     * @param array<mixed> $standing
     */
    private static function standing(array $standing, int $position): HowItsSourceStands
    {
        $said = self::text($standing, PluginsField::Sources, WireField::Standing, $position);

        return match (WhereItsSourceStands::tryFrom($said)) {
            WhereItsSourceStands::Reachable => HowItsSourceStands::reachable(),
            WhereItsSourceStands::Unreachable => HowItsSourceStands::unreachable(self::text($standing, PluginsField::Sources, WireField::Why, $position)),
            WhereItsSourceStands::Unasked => HowItsSourceStands::unasked(self::text($standing, PluginsField::Sources, WireField::Why, $position)),
            // The stack says a plugin it said nothing of by leaving it out,
            // so the word for that is never one a reader takes from the wire.
            WhereItsSourceStands::NotSaid, null => throw PluginsAreUnreadable::said(WireField::Standing, $said),
        };
    }

    /**
     * The reading's name, or empty where the answer names none.
     *
     * @param array<mixed> $data
     */
    private static function agreement(array $data): string
    {
        if (! array_key_exists(WireField::Agreement->value, $data) || $data[WireField::Agreement->value] === null) {
            return '';
        }

        if (! is_string($data[WireField::Agreement->value])) {
            throw PluginsAreUnreadable::missing(WireField::Agreement);
        }

        return $data[WireField::Agreement->value];
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
