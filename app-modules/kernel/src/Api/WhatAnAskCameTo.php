<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * What asking for a title came to, or would come to where it was a rehearsal.
 *
 * `said` is the core's household sentence for all of it: whether it waits for
 * approval and what it leaves of the member's allowance. It is shown as it
 * came, and never composed here out of the parts beside it on the wire.
 */
final readonly class WhatAnAskCameTo
{
    private function __construct(
        private WhatAnAskBecame $became,
        private WhetherItWasRehearsed $rehearsed,
        private string $said,
    ) {}

    /** What the core answered, refused where its sentence is blank. */
    public static function answered(WhatAnAskBecame $became, WhetherItWasRehearsed $rehearsed, string $said): self
    {
        if (trim($said) === '') {
            throw FoundTitleSaysNothing::about('said');
        }

        return new self($became, $rehearsed, $said);
    }

    /** What it became. */
    public function became(): WhatAnAskBecame
    {
        return $this->became;
    }

    /** Whether it was a rehearsal, which asked for nothing. */
    public function wasRehearsed(): bool
    {
        return $this->rehearsed === WhetherItWasRehearsed::Rehearsed;
    }

    /** The core's sentence for it. */
    public function said(): string
    {
        return $this->said;
    }
}
