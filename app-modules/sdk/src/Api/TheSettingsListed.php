<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function array_map;
use function is_array;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Modules\Kernel\Api\AChoice;
use Modules\Kernel\Api\ASecret;
use Modules\Kernel\Api\AServiceReadingIt;
use Modules\Kernel\Api\ASetting;
use Modules\Kernel\Api\ServiceId;
use Modules\Kernel\Api\TheChoices;
use Modules\Kernel\Api\TheSecretsRead;
use Modules\Kernel\Api\TheServicesReadingIt;
use Modules\Kernel\Api\TheServicesSettings;
use Modules\Kernel\Api\TheSettingsRead;
use Modules\Kernel\Api\WhatAChoiceNeeds;
use Modules\Kernel\Api\WhereASecretCameFrom;
use Modules\Kernel\Api\WhoseASettingIs;
use Modules\Sdk\Api\Fields\SettingsField;
use Modules\Sdk\Internal\APath;
use Modules\Sdk\Internal\Optional;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\SettingsRows;
use Modules\Sdk\Internal\TheWaitingRead;
use Modules\Sdk\Internal\WhichOwner;

/**
 * Reads the `settings` envelope into every setting and secret the services read, each setting with its choices, and every service held back.
 *
 * Refused whole with {@see SettingsAreUnreadable} where any part cannot be read.
 */
final readonly class TheSettingsListed
{
    /**
     * Every setting and secret.
     *
     * @param Envelope<mixed> $envelope the `settings` envelope, as the client returned it
     */
    public static function in(Envelope $envelope): TheServicesSettings
    {
        $data = SettingsRows::payload($envelope);
        $settings = [];
        $secrets = [];

        foreach (SettingsRows::listed(Required::rows($data, WireField::Settings, SettingsAreUnreadable::missing(WireField::Settings->value)), WireField::Settings->value) as $position => $setting) {
            $settings[] = self::setting($setting, APath::beneath(WireField::Settings->value, $position));
        }

        foreach (SettingsRows::listed(Required::rows($data, SettingsField::Secrets, SettingsAreUnreadable::missing(SettingsField::Secrets->value)), SettingsField::Secrets->value) as $position => $secret) {
            $secrets[] = self::secret($secret, APath::beneath(SettingsField::Secrets->value, $position));
        }

        return TheServicesSettings::of(TheSettingsRead::these(...$settings), TheSecretsRead::these(...$secrets))
            ->holdingBack(TheWaitingRead::in($data, SettingsAreUnreadable::missing(WireField::Waiting->value)));
    }

    private static function setting(mixed $row, string $where): ASetting
    {
        if (! is_array($row)) {
            throw SettingsAreUnreadable::missing($where);
        }

        return ASetting::read(
            Required::text($row, WireField::Name, SettingsAreUnreadable::missing(APath::beneath($where, WireField::Name->value))),
            self::whose($row, $where),
            self::readers($row, $where),
            SettingsRows::held($row, WireField::Value, $where),
        )->among(self::choices($row, APath::beneath($where, WireField::Choices->value)));
    }

    /**
     * The values a setting accepts, or none where it lists none.
     *
     * @param array<mixed> $row
     */
    private static function choices(array $row, string $where): TheChoices
    {
        if (! array_key_exists(WireField::Choices->value, $row) || $row[WireField::Choices->value] === null) {
            return TheChoices::none();
        }

        if (! is_array($row[WireField::Choices->value])) {
            throw SettingsAreUnreadable::missing($where);
        }

        $choices = [];

        foreach (SettingsRows::listed($row[WireField::Choices->value], $where) as $position => $choice) {
            $choices[] = self::choice($choice, APath::beneath($where, $position));
        }

        return TheChoices::these(...$choices);
    }

    private static function choice(mixed $choice, string $where): AChoice
    {
        if (! is_array($choice) || ! array_key_exists(WireField::Value->value, $choice) || ! is_string($choice[WireField::Value->value])) {
            throw SettingsAreUnreadable::missing(APath::beneath($where, WireField::Value->value));
        }

        return AChoice::of(
            $choice[WireField::Value->value],
            self::needs(Required::rows($choice, WireField::Needs, SettingsAreUnreadable::missing(APath::beneath($where, WireField::Needs->value))), APath::beneath($where, WireField::Needs->value)),
            Required::flag($choice, SettingsField::Open, SettingsAreUnreadable::missing(APath::beneath($where, SettingsField::Open->value))),
        );
    }

    /**
     * What other settings have to hold for a value to be a choice.
     *
     * @param array<mixed> $needs
     */
    private static function needs(array $needs, string $where): WhatAChoiceNeeds
    {
        $held = WhatAChoiceNeeds::nothing();

        foreach ($needs as $setting => $values) {
            if (! is_string($setting) || ! is_array($values)) {
                throw SettingsAreUnreadable::missing($where);
            }

            $held = $held->and(WhatAChoiceNeeds::holding($setting, ...self::values(SettingsRows::listed($values, APath::beneath($where, $setting)), APath::beneath($where, $setting))));
        }

        return $held;
    }

    /**
     * @param  list<mixed>  $values
     * @return list<string>
     */
    private static function values(array $values, string $where): array
    {
        $said = [];

        foreach ($values as $value) {
            $said[] = is_string($value) ? $value : throw SettingsAreUnreadable::missing($where);
        }

        return $said;
    }

    private static function secret(mixed $row, string $where): ASecret
    {
        if (! is_array($row)) {
            throw SettingsAreUnreadable::missing($where);
        }

        return ASecret::read(
            Required::text($row, WireField::Name, SettingsAreUnreadable::missing(APath::beneath($where, WireField::Name->value))),
            self::whose($row, $where),
            self::readers($row, $where),
            Required::flag($row, WireField::Held, SettingsAreUnreadable::missing(APath::beneath($where, WireField::Held->value))),
            self::from(Optional::text($row, WireField::From, SettingsAreUnreadable::missing(APath::beneath($where, WireField::From->value))), APath::beneath($where, WireField::From->value)),
        );
    }

    /** @param array<mixed> $row */
    private static function whose(array $row, string $at): WhoseASettingIs
    {
        $where = APath::beneath($at, WireField::Whose->value);
        $whose = Required::rows($row, WireField::Whose, SettingsAreUnreadable::missing($where));
        $of = Required::text($whose, WireField::Of, SettingsAreUnreadable::missing(APath::beneath($where, WireField::Of->value)));

        return match (WhichOwner::tryFrom($of)) {
            WhichOwner::Core => WhoseASettingIs::theCores(),
            WhichOwner::Namespace => WhoseASettingIs::theNamespaces(Required::text($whose, SettingsField::Namespace, SettingsAreUnreadable::missing(APath::beneath($where, SettingsField::Namespace->value)))),
            null => throw SettingsAreUnreadable::word(APath::beneath($where, WireField::Of->value), $of, ...array_map(static fn(WhichOwner $case): string => $case->value, WhichOwner::cases())),
        };
    }

    /** @param array<mixed> $row */
    private static function readers(array $row, string $at): TheServicesReadingIt
    {
        $where = APath::beneath($at, SettingsField::Readers->value);
        $readers = [];

        foreach (SettingsRows::listed(Required::rows($row, SettingsField::Readers, SettingsAreUnreadable::missing($where)), $where) as $position => $reader) {
            $readers[] = self::reader($reader, APath::beneath($where, $position));
        }

        return TheServicesReadingIt::these(...$readers);
    }

    private static function reader(mixed $reader, string $where): AServiceReadingIt
    {
        if (! is_array($reader)) {
            throw SettingsAreUnreadable::missing($where);
        }

        return AServiceReadingIt::of(
            ServiceId::called(Required::text($reader, WireField::Service, SettingsAreUnreadable::missing(APath::beneath($where, WireField::Service->value)))),
            Required::text($reader, SettingsField::Variable, SettingsAreUnreadable::missing(APath::beneath($where, SettingsField::Variable->value))),
            SettingsRows::held($reader, SettingsField::Default, $where),
        );
    }

    private static function from(string $said, string $where): WhereASecretCameFrom
    {
        return match ($said) {
            '' => WhereASecretCameFrom::Unsaid,
            WhereASecretCameFrom::Operator->value => WhereASecretCameFrom::Operator,
            WhereASecretCameFrom::Recipe->value => WhereASecretCameFrom::Recipe,
            WhereASecretCameFrom::Granted->value => WhereASecretCameFrom::Granted,
            WhereASecretCameFrom::Minted->value => WhereASecretCameFrom::Minted,
            default => throw SettingsAreUnreadable::word($where, $said, WhereASecretCameFrom::Operator->value, WhereASecretCameFrom::Recipe->value, WhereASecretCameFrom::Granted->value, WhereASecretCameFrom::Minted->value),
        };
    }
}
