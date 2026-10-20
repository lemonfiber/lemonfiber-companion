<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What a run of a setting's action came to: the change, what it regenerates and restarts, what the run did, and every setting after it.
 */
final readonly class TheSettingAsChanged
{
    private function __construct(
        private string $setting,
        private ASettingValue $value,
        private ASettingValue $was,
        private Services $regenerates,
        private Services $restarts,
        private WhatTheSettingRunDid $did,
        private AnOffer $offer,
        private string $consequence,
        private string $unrestarted,
        private TheServicesSettings $settings,
    ) {}

    /** The report, as the stack wrote it. */
    public static function said(
        ASettingChange $change,
        ASettingValue $was,
        Services $regenerates,
        Services $restarts,
        WhatTheSettingRunDid $did,
        AnOffer $offer,
        string $consequence,
        string $unrestarted,
        TheServicesSettings $settings,
    ): self {
        return new self($change->setting(), $change->value(), $was, $regenerates, $restarts, $did, $offer, $consequence, $unrestarted, $settings);
    }

    /** The setting, by its name. */
    public function setting(): string
    {
        return $this->setting;
    }

    /** What it is to hold, or nothing where it goes back to its default. */
    public function value(): ASettingValue
    {
        return $this->value;
    }

    /** What it held before. */
    public function was(): ASettingValue
    {
        return $this->was;
    }

    /** Every service whose entry the change regenerates. */
    public function regenerates(): Services
    {
        return $this->regenerates;
    }

    /** Every running service the change restarts. */
    public function restarts(): Services
    {
        return $this->restarts;
    }

    /** What the run did. */
    public function did(): WhatTheSettingRunDid
    {
        return $this->did;
    }

    /** What the reading names itself, which a yes to it carries back. */
    public function offer(): AnOffer
    {
        return $this->offer;
    }

    /** What the change costs, in the stack's words, or blank where it costs nothing. */
    public function consequence(): string
    {
        return $this->consequence;
    }

    /** Why the services it restarts were not restarted, in the stack's words, or blank where they were. */
    public function unrestarted(): string
    {
        return $this->unrestarted;
    }

    /** Every setting and secret after the run. */
    public function settings(): TheServicesSettings
    {
        return $this->settings;
    }

    /** Whether this is a rehearsal of that change. */
    public function rehearses(ASettingChange $change): bool
    {
        return $this->did === WhatTheSettingRunDid::Rehearsed && $this->setting === $change->setting() && $this->value->said() === $change->value()->said() && $this->value->isHeld() === $change->value()->isHeld();
    }
}
