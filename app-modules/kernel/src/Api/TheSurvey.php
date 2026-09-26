<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What a stack found already on its machine, before anything is proposed.
 *
 * **Whether it looked is its own fact.** An engine that could not be asked
 * finds nothing, and so does an empty machine; the first is carried as not
 * having looked, so the two are never one answer.
 */
final readonly class TheSurvey
{
    private function __construct(
        private bool $looked,
        private WhatStandsHere $standing,
        private ThePortsHeld $conflicts,
        private WhatIsUnsupported $unsupported,
        private ThePortsMoved $beside,
        private WhatLinkingCosts $linking,
        private WhatMayBeDone $choices,
    ) {}

    /** What the stack said it found, whether it could look at all, and what may be done about it. */
    public static function reported(
        bool $looked,
        WhatStandsHere $standing,
        ThePortsHeld $conflicts,
        WhatIsUnsupported $unsupported,
        ThePortsMoved $beside,
        WhatLinkingCosts $linking,
        WhatMayBeDone $choices,
    ): self {
        return new self($looked, $standing, $conflicts, $unsupported, $beside, $linking, $choices);
    }

    /** Whether the engine answered, which is what tells an empty machine from an unread one. */
    public function looked(): bool
    {
        return $this->looked;
    }

    /** Every project already here that is not lemonfiber's, with its services. */
    public function standing(): WhatStandsHere
    {
        return $this->standing;
    }

    /** Every port lemonfiber wants that something already here holds. */
    public function conflicts(): ThePortsHeld
    {
        return $this->conflicts;
    }

    /** What was found and cannot be taken over, and why. */
    public function unsupported(): WhatIsUnsupported
    {
        return $this->unsupported;
    }

    /** Where each service would listen to run beside what is here. */
    public function beside(): ThePortsMoved
    {
        return $this->beside;
    }

    /** What the existing layout costs where it cannot hold a hardlink. */
    public function linking(): WhatLinkingCosts
    {
        return $this->linking;
    }

    /** The modes, what adopting each service would come to, and what no mode carries. */
    public function choices(): WhatMayBeDone
    {
        return $this->choices;
    }
}
