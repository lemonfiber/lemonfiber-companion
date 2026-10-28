<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function array_key_exists;
use function is_array;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\LifecycleEnvelope;
use Modules\Kernel\Api\TheCommandLine;
use Modules\Kernel\Api\TheServicesNamed;
use Modules\Kernel\Api\TheSwitch;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Sdk\Api\Fields\LifecycleField;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\Wire;

use function trim;

/**
 * The `lifecycle` envelope a `switch` run answers with, as whether it was rehearsed and what it stopped, started and kept.
 */
final readonly class WhatTheSwitchCameTo
{
    /** @param Envelope<mixed> $envelope the envelope the finished work redeemed */
    public static function in(Envelope $envelope): TheSwitch
    {
        $data = self::payload($envelope);
        $switched = Required::rows($data, LifecycleField::Switched, LifecycleIsUnreadable::missing(LifecycleField::Switched));
        $report = TheSwitch::said(
            WhetherItWasRehearsed::said(Required::flag($data, WireField::Rehearsed, LifecycleIsUnreadable::missing(WireField::Rehearsed))),
            self::named($switched, WireField::Stopped),
            self::named($switched, WireField::Started),
            self::named($switched, WireField::Kept),
        );

        if (! array_key_exists(LifecycleField::StopCommand->value, $switched) || $switched[LifecycleField::StopCommand->value] === null) {
            return $report;
        }

        return $report->stoppingWith(self::command($switched));
    }

    /**
     * @param  Envelope<mixed> $envelope
     * @return array<mixed>
     */
    private static function payload(Envelope $envelope): array
    {
        $data = self::data(Wire::checked($envelope));

        if (! is_array($data)) {
            throw LifecycleIsUnreadable::missing(WireField::Data);
        }

        return $data;
    }

    /** @param Envelope<mixed> $envelope */
    private static function data(Envelope $envelope): mixed
    {
        return LifecycleEnvelope::in($envelope)->data;
    }

    /** @param array<mixed> $switched */
    private static function named(array $switched, NamesAWireField $field): TheServicesNamed
    {
        $names = [];

        foreach (Required::rows($switched, $field, LifecycleIsUnreadable::missing($field)) as $position => $name) {
            $names[] = is_string($name) && trim($name) !== '' ? $name : throw LifecycleIsUnreadable::entry($field, (int) $position);
        }

        return TheServicesNamed::of(...$names);
    }

    /** @param array<mixed> $switched */
    private static function command(array $switched): TheCommandLine
    {
        $words = [];

        foreach (Required::rows($switched, LifecycleField::StopCommand, LifecycleIsUnreadable::missing(LifecycleField::StopCommand)) as $position => $word) {
            $words[] = Required::isText($word) ? $word : throw LifecycleIsUnreadable::entry(LifecycleField::StopCommand, (int) $position);
        }

        return $words === [] ? throw LifecycleIsUnreadable::missing(LifecycleField::StopCommand) : TheCommandLine::of(...$words);
    }
}
