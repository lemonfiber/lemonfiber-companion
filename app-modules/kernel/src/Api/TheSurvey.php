<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * What a stack found already on its machine, before anything is proposed.
 *
 * **Whether it looked is its own fact.** An engine that could not be asked
 * finds nothing, and so does an empty machine; the first is carried as not
 * having looked, so the two are never one answer.
 *
 * What adopting each service would come to and what no mode carries are read
 * here with the rest, because they belong to the choice between the modes
 * rather than to the look.
 */
final readonly class TheSurvey
{
    private function __construct(
        private bool $looked,
        private WhatStandsHere $standing,
        private ThePortsHeld $conflicts,
        private WhatIsUnsupported $unsupported,
        private TheModes $modes,
        private ThePortsMoved $beside,
        private WhatLinkingCosts $linking,
        private WhatAdoptingWouldDo $carrying,
        private WhatIsUnsupported $notCarried,
    ) {}

    /** What the stack said it found, and whether it could look at all. */
    public static function reported(
        bool $looked,
        WhatStandsHere $standing,
        ThePortsHeld $conflicts,
        WhatIsUnsupported $unsupported,
        TheModes $modes,
        ThePortsMoved $beside,
        WhatLinkingCosts $linking,
        WhatAdoptingWouldDo $carrying,
        WhatIsUnsupported $notCarried,
    ): self {
        return new self($looked, $standing, $conflicts, $unsupported, $modes, $beside, $linking, $carrying, $notCarried);
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

    /** What may be done about what was found, in the stack's order. */
    public function modes(): TheModes
    {
        return $this->modes;
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

    /** What adopting each recognised service would come to. */
    public function carrying(): WhatAdoptingWouldDo
    {
        return $this->carrying;
    }

    /** What no mode carries across, whichever runs. */
    public function notCarried(): WhatIsUnsupported
    {
        return $this->notCarried;
    }
}
