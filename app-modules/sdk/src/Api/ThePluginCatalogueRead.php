<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function array_map;
use function is_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\PluginCatalogueEnvelope;
use Modules\Kernel\Api\ACatalogueEntry;
use Modules\Kernel\Api\ASettingThatReaches;
use Modules\Kernel\Api\Instant;
use Modules\Kernel\Api\ThePluginCatalogue;
use Modules\Sdk\Api\Fields\PluginCatalogueField;
use Modules\Sdk\Internal\HowThePluginCatalogueStands;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\Wire;

/**
 * The stack's plugin catalogue, read off the `plugin-catalogue` envelope.
 *
 * Listed, switched off and not reached are each read from their own fields;
 * an answer missing one of its own is refused, never read as an empty list.
 * The setting that turns reach on is taken only with the write this app puts
 * to a review, which the kernel's value checks.
 */
final readonly class ThePluginCatalogueRead
{
    /** @param Envelope<mixed> $envelope the `plugin-catalogue` envelope, as the client returned it */
    public static function in(Envelope $envelope): ThePluginCatalogue
    {
        $data = self::payload(Wire::checked($envelope));

        if (! is_array($data)) {
            throw PluginCatalogueIsUnreadable::missing(WireField::Data->value);
        }

        return match (self::state($data)) {
            HowThePluginCatalogueStands::Listed => ThePluginCatalogue::listed(
                Instant::atEpochSeconds(Required::number($data, PluginCatalogueField::Published, PluginCatalogueIsUnreadable::missing(PluginCatalogueField::Published->value))),
                Required::count($data, PluginCatalogueField::Serial, PluginCatalogueIsUnreadable::missing(PluginCatalogueField::Serial->value)),
                Required::count($data, PluginCatalogueField::Unreadable, PluginCatalogueIsUnreadable::missing(PluginCatalogueField::Unreadable->value)),
                ...self::entries($data),
            ),
            HowThePluginCatalogueStands::Off => ThePluginCatalogue::switchedOff(self::setting(self::table($data, WireField::Setting, WireField::Setting->value))),
            HowThePluginCatalogueStands::Failed => self::failure(self::table($data, PluginCatalogueField::Failure, PluginCatalogueField::Failure->value)),
        };
    }

    /** @param Envelope<mixed> $envelope */
    private static function payload(Envelope $envelope): mixed
    {
        return PluginCatalogueEnvelope::in($envelope)->data;
    }

    /** @param array<mixed> $data */
    private static function state(array $data): HowThePluginCatalogueStands
    {
        $said = Required::text($data, WireField::State, PluginCatalogueIsUnreadable::missing(WireField::State->value));

        return HowThePluginCatalogueStands::tryFrom($said) ?? throw PluginCatalogueIsUnreadable::word(WireField::State, $said, ...array_map(static fn(HowThePluginCatalogueStands $one): string => $one->value, HowThePluginCatalogueStands::cases()));
    }

    /**
     * @param  array<mixed>         $data
     * @return list<ACatalogueEntry>
     */
    private static function entries(array $data): array
    {
        $entries = [];

        foreach (Required::rows($data, WireField::Entries, PluginCatalogueIsUnreadable::missing(WireField::Entries->value)) as $row) {
            $entries[] = self::entry(self::row($row));
        }

        return $entries;
    }

    /** @param array<mixed> $row */
    private static function entry(array $row): ACatalogueEntry
    {
        $id = self::word($row, WireField::Id);
        $name = self::word($row, WireField::Name);
        $description = self::word($row, PluginCatalogueField::Description);
        $version = self::word($row, WireField::Version);
        $withoutIt = self::word($row, WireField::WithoutIt);

        if (! array_key_exists(WireField::Installed->value, $row) || $row[WireField::Installed->value] === null) {
            return ACatalogueEntry::offered($id, $name, $description, $version, $withoutIt);
        }

        $installed = self::table($row, WireField::Installed, WireField::Installed->under(WireField::Entries));

        return ACatalogueEntry::installedHere(
            $id,
            $name,
            $description,
            $version,
            $withoutIt,
            Required::text($installed, WireField::Version, PluginCatalogueIsUnreadable::missing(WireField::Version->under(WireField::Installed))),
            Required::flag($installed, PluginCatalogueField::Newer, PluginCatalogueIsUnreadable::missing(PluginCatalogueField::Newer->under(WireField::Installed))),
        );
    }

    /** @param array<mixed> $setting */
    private static function setting(array $setting): ASettingThatReaches
    {
        return ASettingThatReaches::named(
            Required::text($setting, WireField::Key, PluginCatalogueIsUnreadable::missing(WireField::Key->under(WireField::Setting))),
            Required::text($setting, WireField::Value, PluginCatalogueIsUnreadable::missing(WireField::Value->under(WireField::Setting))),
            Required::text($setting, WireField::Action, PluginCatalogueIsUnreadable::missing(WireField::Action->under(WireField::Setting))),
        );
    }

    /** @param array<mixed> $failure */
    private static function failure(array $failure): ThePluginCatalogue
    {
        return ThePluginCatalogue::unreached(
            Required::text($failure, WireField::Code, PluginCatalogueIsUnreadable::missing(WireField::Code->under(PluginCatalogueField::Failure))),
            Required::text($failure, WireField::Summary, PluginCatalogueIsUnreadable::missing(WireField::Summary->under(PluginCatalogueField::Failure))),
        );
    }

    /** @param array<mixed> $row */
    private static function word(array $row, NamesAWireField $field): string
    {
        return Required::text($row, $field, PluginCatalogueIsUnreadable::missing($field->under(WireField::Entries)));
    }

    /** @return array<mixed> */
    private static function row(mixed $row): array
    {
        return is_array($row) ? $row : throw PluginCatalogueIsUnreadable::missing(WireField::Entries->value);
    }

    /**
     * @param  array<mixed> $table
     * @return array<mixed>
     */
    private static function table(array $table, NamesAWireField $field, string $path): array
    {
        return array_key_exists($field->value, $table) && is_array($table[$field->value]) ? $table[$field->value] : throw PluginCatalogueIsUnreadable::missing($path);
    }
}
