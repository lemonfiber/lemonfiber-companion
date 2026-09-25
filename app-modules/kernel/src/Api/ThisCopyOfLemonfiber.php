<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * The running copy of lemonfiber: what version it is, how it got there, and what moving it would come to.
 *
 * Read and never acted on. Nothing here updates anything; the command that
 * would is shown, for the operator to run at the machine.
 *
 * `untold` is empty where the stack said nothing.
 */
final readonly class ThisCopyOfLemonfiber
{
    private function __construct(
        private string $running,
        private HowThisCopyGotThere $gotThere,
        private WhereThisCopyStands $stands,
        private WhatIsReleased $released,
        private string $untold,
        private HowItWouldBeUpdated $by,
        private WhatAnUpdateWouldBring $brings,
    ) {}

    /**
     * What the stack said of its running copy.
     *
     * `$untold` may be empty; a blank one is refused, since blank is a
     * sentence with nothing in it.
     */
    public static function reported(
        string $running,
        HowThisCopyGotThere $gotThere,
        WhereThisCopyStands $stands,
        WhatIsReleased $released,
        string $untold,
        HowItWouldBeUpdated $by,
        WhatAnUpdateWouldBring $brings,
    ): self {
        if (trim($running) === '') {
            throw ItselfSaysNothing::about('running');
        }

        if ($untold !== '' && trim($untold) === '') {
            throw ItselfSaysNothing::about('untold');
        }

        return new self($running, $gotThere, $stands, $released, $untold, $by, $brings);
    }

    /** The version running now. */
    public function running(): string
    {
        return $this->running;
    }

    /** How this copy got onto the machine, and who owns it. */
    public function gotThere(): HowThisCopyGotThere
    {
        return $this->gotThere;
    }

    /** Where it stands against what has been released. */
    public function stands(): WhereThisCopyStands
    {
        return $this->stands;
    }

    /** The newest version released and what it changed. */
    public function released(): WhatIsReleased
    {
        return $this->released;
    }

    /** Why availability could not be told, or empty where it could. */
    public function untold(): string
    {
        return $this->untold;
    }

    /** The command that would update it, or why there is none. */
    public function updatedBy(): HowItWouldBeUpdated
    {
        return $this->by;
    }

    /** What an update would bring and leave behind. */
    public function brings(): WhatAnUpdateWouldBring
    {
        return $this->brings;
    }
}
