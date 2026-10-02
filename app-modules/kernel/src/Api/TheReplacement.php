<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function count;

/**
 * What standing in place of a setup already here came to, or would come to.
 *
 * The services it would stop, the ones it stopped, and the ones that would not
 * stop and are still up. Nothing is deleted: the old setup can be started
 * again. A project of `''` is the stack naming none.
 *
 * It names its offer: `agreement` is the name of what it would stop, and the
 * yes to a replacement is that name, so a container started since it was read
 * is refused rather than stopped unseen. `''` where the stack named no offer,
 * which is a stack that takes a bare confirmation instead.
 */
final readonly class TheReplacement
{
    private function __construct(
        private string $project,
        private WhatWasNamed $wouldStop,
        private WhatWasNamed $stopped,
        private WhatWasNamed $stillRunning,
        private string $agreement,
    ) {}

    /** What the stack said, as it said it. */
    public static function of(string $project, WhatWasNamed $wouldStop, WhatWasNamed $stopped, WhatWasNamed $stillRunning, string $agreement): self
    {
        return new self($project, $wouldStop, $stopped, $stillRunning, trim($agreement));
    }

    /** The name of the offer this answers, or `''` where the stack named none. */
    public function agreement(): string
    {
        return $this->agreement;
    }

    /** The project that would be stood in place of, or `''` where the stack named none. */
    public function project(): string
    {
        return $this->project;
    }

    /** The services that would be stopped. */
    public function wouldStop(): WhatWasNamed
    {
        return $this->wouldStop;
    }

    /** The services that were stopped. */
    public function stopped(): WhatWasNamed
    {
        return $this->stopped;
    }

    /** The services that would not stop and are still up. */
    public function stillRunning(): WhatWasNamed
    {
        return $this->stillRunning;
    }

    /**
     * Whether anything it stands in place of is still up.
     *
     * A replacement that left something running is not finished, whatever
     * stance the stack gave it: two setups answering for one service is the
     * state replacing exists to end.
     */
    public function leftSomethingRunning(): bool
    {
        return count($this->stillRunning) > 0;
    }
}
