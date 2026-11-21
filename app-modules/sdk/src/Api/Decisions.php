<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\ConfigEnvelope;
use Modules\Kernel\Api\ADecision;
use Modules\Kernel\Api\Cost;
use Modules\Kernel\Api\WhatSetupDecided;
use Modules\Sdk\Api\Fields\ConfigField;
use Modules\Sdk\Internal\InTheOrderSent;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\Wire;

/** The `config` envelope, read as every decision setup makes beside the settings it lists. */
final readonly class Decisions
{
    /** @param Envelope<mixed> $envelope the `config` envelope */
    public static function in(Envelope $envelope): WhatSetupDecided
    {
        $decisions = [];

        foreach (InTheOrderSent::rows(self::listed(ConfigEnvelope::in(Wire::checked($envelope))->data)) as $position => $row) {
            if (! is_array($row)) {
                throw SettingIsUnreadable::finding(ConfigField::Decisions, $position);
            }

            $decisions[] = self::one($row, SettingIsUnreadable::finding(ConfigField::Decisions, $position));
        }

        return WhatSetupDecided::beside(Dials::in($envelope), ...$decisions);
    }

    /**
     * The `decisions` list, refused unless the answer is a table holding one.
     *
     * @return array<mixed>
     */
    private static function listed(mixed $data): array
    {
        if (! is_array($data)) {
            throw SettingIsUnreadable::missing(ConfigField::Decisions);
        }

        return Required::rows($data, ConfigField::Decisions, SettingIsUnreadable::missing(ConfigField::Decisions));
    }

    /** @param array<mixed> $row */
    private static function one(array $row, SettingIsUnreadable $refused): ADecision
    {
        $key = Required::text($row, ConfigField::Key, $refused);
        $cost = Cost::tryFrom(Required::text($row, WireField::Cost, $refused)) ?? throw $refused;
        $affects = Required::text($row, ConfigField::Affects, $refused);
        $held = Required::flag($row, WireField::Held, $refused);
        $credential = Required::flag($row, WireField::Credential, $refused);

        if (! array_key_exists(WireField::Setting->value, $row) || $row[WireField::Setting->value] === null) {
            return ADecision::inTheConfiguration($key, $cost, $affects, held: $held, credential: $credential);
        }

        return ADecision::inTheSettings($key, $cost, $affects, held: $held, credential: $credential, setting: Required::text($row, WireField::Setting, $refused));
    }
}
