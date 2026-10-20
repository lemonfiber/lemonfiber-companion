<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What a run of a setting's action came to: the change, what it held before, what it reaches, what the run did, and every setting after it.
 */
final readonly class TheSettingAsChanged
{
    private function __construct(
        private ASettingChange $change,
        private ASettingValue $was,
        private WhatTheChangeReaches $reaches,
        private WhatTheSettingRunDid $did,
        private AnOffer $offer,
        private TheServicesSettings $settings,
    ) {}

    /** The report, as the stack wrote it. */
    public static function said(ASettingChange $change, ASettingValue $was, WhatTheChangeReaches $reaches, WhatTheSettingRunDid $did, AnOffer $offer, TheServicesSettings $settings): self
    {
        return new self($change, $was, $reaches, $did, $offer, $settings);
    }

    /** The setting, by its name. */
    public function setting(): string
    {
        return $this->change->setting();
    }

    /** What it is to hold, or nothing where it goes back to its default. */
    public function value(): ASettingValue
    {
        return $this->change->value();
    }

    /** What it held before. */
    public function was(): ASettingValue
    {
        return $this->was;
    }

    /** What the change reaches beyond the setting. */
    public function reaches(): WhatTheChangeReaches
    {
        return $this->reaches;
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

    /** Every setting and secret after the run. */
    public function settings(): TheServicesSettings
    {
        return $this->settings;
    }

    /** Whether this is a rehearsal of that change. */
    public function rehearses(ASettingChange $change): bool
    {
        return $this->did === WhatTheSettingRunDid::Rehearsed
            && $this->change->how() === $change->how()
            && $this->change->setting() === $change->setting()
            && $this->change->value()->said() === $change->value()->said();
    }
}
