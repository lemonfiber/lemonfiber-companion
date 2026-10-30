<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What a run of a settings action came to: each setting it moves, what the change reaches, what the run did, and every setting after it.
 */
final readonly class TheSettingAsChanged
{
    private function __construct(
        private TheSettingMoves $moves,
        private WhatTheChangeReaches $reaches,
        private WhatTheSettingRunDid $did,
        private AnOffer $offer,
        private TheServicesSettings $settings,
    ) {}

    /** The report, as the stack wrote it. */
    public static function said(TheSettingMoves $moves, WhatTheChangeReaches $reaches, WhatTheSettingRunDid $did, AnOffer $offer, TheServicesSettings $settings): self
    {
        return new self($moves, $reaches, $did, $offer, $settings);
    }

    /** Each setting the change moves. */
    public function moves(): TheSettingMoves
    {
        return $this->moves;
    }

    /** The change the moves make. */
    public function change(): ASettingChange
    {
        return $this->moves->change();
    }

    /** What the change reaches beyond the settings. */
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
        return $this->did === WhatTheSettingRunDid::Rehearsed && $this->change()->isTheSameAs($change);
    }
}
