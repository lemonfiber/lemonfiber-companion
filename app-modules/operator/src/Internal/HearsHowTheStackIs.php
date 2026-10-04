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
 * summary does with what it holds: open it out to what it counts, say it as one
 * line, and open the stream as the screen mounts, opened out where What's new
 * asked for that.
 *
 * **A screen opening on a stack starts from the summary kept from before**, as
 * of when it was read, rather than from nothing, because the stream keeps every
 * summary it hears for the next opening.
 *
 * @phpstan-require-extends NativeComponent
 */
trait HearsHowTheStackIs
{
    /**
     * What a screen opening this one hands it to have what is wrong opened out.
     *
     * What's new opens a problem here, and the operator arrives on the list of
     * what is wrong rather than having to ask for it.
     */
    public const string WHATS_WRONG_OPENED = 'whats_wrong_opened';

    /** Whether the operator has opened the summary out to what it counts. */
    public bool $expanded = false;

    public function mount(): void
    {
        $this->expanded = $this->expanded || $this->data(self::WHATS_WRONG_OPENED) === true;
        $this->listen();
    }

    /** Open the summary out to what it counts, or fold it back. */
    public function expand(): void
    {
        $this->expanded = ! $this->expanded;
    }

    /** The summary as the template draws it. */
    public function summary(): WhatTheOneLineSays
    {
        return new HowTheOneLineReads()->of($this->heardSoFar(), $this->listening->clock->now());
    }
}
