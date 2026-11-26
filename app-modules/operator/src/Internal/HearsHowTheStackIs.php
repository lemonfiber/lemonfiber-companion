<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Operator\Internal\Presenters\HowTheOneLineReads;
use Modules\Operator\Internal\ViewModels\WhatTheOneLineSays;
use Native\Mobile\Edge\NativeComponent;

/**
 * The health summary a screen shows, held rather than read.
 *
 * The core publishes the summary on its event stream and nowhere else, and
 * every operator's screen about a stack holds that stream through
 * {@see HoldsItsStacksStream}. What is here is what only the screen drawing the
 * summary does with what it holds: say it as one line, with what it counts
 * drawn under it, and open the stream as the screen mounts.
 *
 * **A screen opening on a stack starts from the summary kept from before**, as
 * of when it was read, rather than from nothing, because the stream keeps every
 * summary it hears for the next opening.
 *
 * **Opening it sees whether some alerts may be missing.** Where a resumed
 * stream marked the stack, on a wake or in front of the operator, the screen
 * says so for as long as it is open, and opening it clears the mark. A stream
 * this screen resumes that marks the stack says so at once, the same way.
 *
 * @phpstan-require-extends NativeComponent
 */
trait HearsHowTheStackIs
{
    /** Whether a resumed stream marked this stack as maybe missing alerts, when the screen opened or since. */
    public bool $someAlertsMayBeMissing = false;

    public function mount(): void
    {
        $this->someAlertsMayBeMissing = $this->listening->missing->seenOn($this->stack()->id());
        $this->listen();
    }

    /** The summary as the template draws it. */
    public function summary(): WhatTheOneLineSays
    {
        return new HowTheOneLineReads()->of($this->heardSoFar(), $this->listening->clock->now());
    }

    /** The stream this screen resumed could not vouch for every alert: say so while the screen is open, and see the mark it made. */
    protected function heardSomeAlertsMayBeMissing(): void
    {
        $this->listening->missing->seenOn($this->stack()->id());
        $this->someAlertsMayBeMissing = true;
    }
}
