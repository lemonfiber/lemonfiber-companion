<?php

declare(strict_types=1);

namespace Modules\Sdk\Api;

use function is_array;
use function is_string;

use Lemonfiber\Sdk\Envelope\Envelope;
use Lemonfiber\Sdk\Generated\HouseholdEnvelope;
use Modules\Kernel\Api\Sentence;
use Modules\Kernel\Api\Sentences;
use Modules\Kernel\Api\TheAllowanceAsChanged;
use Modules\Kernel\Api\WhetherItWasRehearsed;
use Modules\Sdk\Internal\Required;
use Modules\Sdk\Internal\Wire;

use function trim;

/**
 * The `household` envelope a `household-allow` run answers with, as whether it was rehearsed and what the stack said of it.
 */
final readonly class WhatTheAllowanceCameTo
{
    /** @param Envelope<mixed> $envelope the envelope the finished work redeemed */
    public static function in(Envelope $envelope): TheAllowanceAsChanged
    {
        $data = self::payload($envelope);
        $said = [];

        foreach (Required::rows($data, WireField::Findings, HouseholdIsUnreadable::missing(WireField::Findings)) as $position => $finding) {
            $said[] = self::sentence($finding, $position);
        }

        return TheAllowanceAsChanged::said(
            WhetherItWasRehearsed::said(Required::flag($data, WireField::Rehearsed, HouseholdIsUnreadable::missing(WireField::Rehearsed))),
            Sentences::of(...$said),
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

    private static function sentence(mixed $finding, int|string $position): Sentence
    {
        if (! is_string($finding) || trim($finding) === '') {
            throw HouseholdIsUnreadable::finding((int) $position);
        }

        return Sentence::of($finding);
    }
}
