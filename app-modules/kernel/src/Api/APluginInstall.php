<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use Closure;

/**
 * What installing a plugin would do, or did: the plugin, every change, every proof, and how it ended.
 *
 * **The same account before and after the yes.** A reading lists what the
 * install would settle, write, prove, override, leave contested and grant
 * beyond every plugin's entry, writing
 * none of it; after the yes the same lists come back with what each proof came
 * to, what the stack's own checks made of it, and either the record written or
 * what putting it back came to.
 *
 * **Installed means all three.** The record written, no proof failed, and the
 * stack's checks reporting nothing broken: anything short of that is never
 * shown as installed.
 */
final readonly class APluginInstall
{
    private function __construct(
        private APlugin $would,
        private bool $recorded,
        private WhatAnInstallChanges $changing,
        private TheProofs $proofs,
        private WhatTheChecksMade $checks,
        private ?ARunPutBack $putBack,
    ) {}

    /** The account of an install nothing was put back from: a reading, or one that was recorded. */
    public static function reported(APlugin $would, bool $recorded, WhatAnInstallChanges $changing, TheProofs $proofs, WhatTheChecksMade $checks): self
    {
        return new self($would, $recorded, $changing, $proofs, $checks, null);
    }

    /** The account of an install that did not hold, and what putting it back came to. Nothing put back is recorded. */
    public static function putBack(APlugin $would, WhatAnInstallChanges $changing, TheProofs $proofs, WhatTheChecksMade $checks, ARunPutBack $putBack): self
    {
        return new self($would, recorded: false, changing: $changing, proofs: $proofs, checks: $checks, putBack: $putBack);
    }

    /** The plugin, as the install settles it. */
    public function would(): APlugin
    {
        return $this->would;
    }

    /** Every change, in order. */
    public function changes(): ThePluginChanges
    {
        return $this->changing->changes();
    }

    /** Every proof, with what asking it came to. */
    public function proofs(): TheProofs
    {
        return $this->proofs;
    }

    /** Every ask it would leave contested. */
    public function contests(): TheContestsLeft
    {
        return $this->changing->contests();
    }

    /** Every bundled setting it changes. */
    public function overrides(): TheSettingsItOverrides
    {
        return $this->changing->overrides();
    }

    /** Every service taking a privileged shape. */
    public function taking(): TheShapesTaken
    {
        return $this->changing->taking();
    }

    /** Every approval it asks for apart from the offer, as the stack spells each: its recipes' values, then its services' shapes. */
    public function approvals(): PluginLines
    {
        $approvals = [];

        foreach ([$this->would->approvals(), $this->changing->taking()->approvals()] as $lines) {
            foreach ($lines as $approval) {
                $approvals[] = $approval;
            }
        }

        return PluginLines::under('approval', ...$approvals);
    }

    /** What the stack's own checks made of it. */
    public function checks(): WhatTheChecksMade
    {
        return $this->checks;
    }

    /**
     * Say what happens where it was put back and where it was not, and get back what you built.
     *
     * @template TPutBack of object
     * @template TNot of object
     *
     * @param Closure(ARunPutBack): TPutBack $putBack
     * @param Closure(): TNot                $notPutBack
     *
     * @return TPutBack|TNot
     */
    public function wasItPutBack(Closure $putBack, Closure $notPutBack): object
    {
        return $this->putBack instanceof ARunPutBack ? $putBack($this->putBack) : $notPutBack();
    }

    /** Whether this is a reading: nothing recorded and nothing put back, so nothing was written. */
    public function isAReading(): bool
    {
        return ! $this->recorded && ! $this->putBack instanceof ARunPutBack;
    }

    /** Whether it is installed: recorded, no proof failed, and the stack's checks found nothing broken. */
    public function held(): bool
    {
        return $this->recorded
            && $this->checks->brokeNothing()
            && $this->proofs->noneStopsAnInstall();
    }
}
