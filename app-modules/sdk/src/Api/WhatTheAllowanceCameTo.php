<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function is_array;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\HouseholdEnvelope;
use Modules\Kernel\Api\Sentence;
use Modules\Kernel\Api\Sentences;
use Modules\Kernel\Api\TheAllowanceAsChanged;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Sdk\Internal\InTheOrderSent;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\WhatEachObstacleSaid;
use Modules\Sdk\Internal\Wire;

/**
 * The `household` envelope a `household-allow` run answers with, as whether it was rehearsed and what the stack said of it.
 */
final readonly class WhatTheAllowanceCameTo
{
    /** @param Envelope<mixed> $envelope the envelope the finished work redeemed */
    public static function in(Envelope $envelope): TheAllowanceAsChanged
    {
        $data = self::payload($envelope);
        $inTheWay = [];
        $noted = [];

        foreach (InTheOrderSent::rows(Required::rows($data, WireField::Findings, HouseholdIsUnreadable::missing(WireField::Findings))) as $position => $obstacle) {
            $inTheWay[] = WhatEachObstacleSaid::named($obstacle, HouseholdIsUnreadable::finding($position));
        }

        foreach (InTheOrderSent::rows(Required::rows($data, WireField::Noted, HouseholdIsUnreadable::missing(WireField::Noted))) as $position => $note) {
            $noted[] = self::note($note, $position);
        }

        return TheAllowanceAsChanged::said(
            WhetherItWasRehearsed::said(Required::flag($data, WireField::Rehearsed, HouseholdIsUnreadable::missing(WireField::Rehearsed))),
            Sentences::of(...$noted),
            ...$inTheWay,
        );
    }

    /**
     * @param  Envelope<mixed> $envelope
     * @return array<mixed>
     */
    private static function payload(Envelope $envelope): array
    {
        $data = self::data(Wire::checked($envelope));

        if (! is_array($data)) {
            throw HouseholdIsUnreadable::missing(WireField::Data);
        }

        return $data;
    }

    /** @param Envelope<mixed> $envelope */
    private static function data(Envelope $envelope): mixed
    {
        return HouseholdEnvelope::in($envelope)->data;
    }

    private static function note(mixed $note, int $position): Sentence
    {
        if (! Required::isText($note)) {
            throw HouseholdIsUnreadable::note($position);
        }

        return Sentence::of($note);
    }
}
