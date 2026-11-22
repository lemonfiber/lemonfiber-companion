<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\ConfigEnvelope;
use Modules\Kernel\Api\AnAreaLeftAlone;
use Modules\Kernel\Api\AThingLeftAlone;
use Modules\Kernel\Api\TheAreasLeftAlone;
use Modules\Kernel\Api\WhatAnAreaCovers;
use Modules\Kernel\Api\WhatIsLeftAlone;
use Modules\Kernel\Api\WhatWasFoundOfTheAreasLeftAlone;
use Modules\Sdk\Api\Fields\ConfigField;
use Modules\Sdk\Internal\InTheOrderSent;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\Wire;

/** The `config` envelope, read as every area the operator told the stack to leave alone, or as a stack too old to say. */
final readonly class AreasLeftAlone
{
    /** @param Envelope<mixed> $envelope the `config` envelope */
    public static function in(Envelope $envelope): WhatWasFoundOfTheAreasLeftAlone
    {
        $data = self::payload($envelope);

        if (! is_array($data)) {
            throw SettingIsUnreadable::missing(ConfigField::Unmanaged);
        }

        if (! array_key_exists(ConfigField::Unmanaged->value, $data)) {
            return WhatWasFoundOfTheAreasLeftAlone::notCarried();
        }

        $refused = SettingIsUnreadable::missing(ConfigField::Unmanaged);
        $areas = [];

        foreach (InTheOrderSent::rows(Required::rows($data, ConfigField::Unmanaged, $refused)) as $position => $row) {
            $areas[] = self::area($row, SettingIsUnreadable::finding(ConfigField::Unmanaged, $position));
        }

        return WhatWasFoundOfTheAreasLeftAlone::listed(TheAreasLeftAlone::these(Required::count($data, ConfigField::UnmanagedMore, $refused), ...$areas));
    }

    /**
     * The `config` payload, read as the stack sent it, so a field an older stack does not send is told apart.
     *
     * @param Envelope<mixed> $envelope
     */
    private static function payload(Envelope $envelope): mixed
    {
        return ConfigEnvelope::in(Wire::checked($envelope))->data;
    }

    private static function area(mixed $row, SettingIsUnreadable $refused): AnAreaLeftAlone
    {
        if (! is_array($row)) {
            throw $refused;
        }

        $covers = [];

        foreach (InTheOrderSent::rows(Required::rows($row, WireField::Covers, $refused)) as $covered) {
            $covers[] = self::covered($covered, $refused);
        }

        return AnAreaLeftAlone::declared(
            Required::text($row, ConfigField::Area, $refused),
            Required::flag($row, ConfigField::AreaCut, $refused),
            Required::text($row, WireField::Why, $refused),
            Required::flag($row, ConfigField::WhyCut, $refused),
            WhatAnAreaCovers::these(Required::count($row, ConfigField::CoversMore, $refused), ...$covers),
        );
    }

    private static function covered(mixed $thing, SettingIsUnreadable $refused): AThingLeftAlone
    {
        if (! is_array($thing)) {
            throw $refused;
        }

        return AThingLeftAlone::of(
            WhatIsLeftAlone::tryFrom(Required::text($thing, WireField::Kind, $refused)) ?? throw $refused,
            Required::text($thing, WireField::Name, $refused),
        );
    }
}
