<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

use Modules\Kernel\Api\Session;
use Modules\Kernel\Api\Stack;
use Modules\Kernel\Api\WhatBecameOfTheWiring;
use Native\Mobile\Edge\NativeComponent;

/**
 * Adopting the operator's own edits as the stack's expected state: rehearsed first, and made only on a separate yes against that rehearsal.
 *
 * The screen that uses it follows the run the way it follows a wiring run, and reads both flags below when the run answers.
 *
 * @phpstan-require-extends NativeComponent
 */
trait AdoptsTheEdits
{
    /** Whether the run being followed is a rehearsal of adopting. */
    public bool $rehearsingAnAdoption = false;

    /** Whether the report on the screen is a rehearsal of adopting a yes may be given to. */
    public bool $adoptionRehearsed = false;

    /** Ask what adopting the operator's own edits would keep and wire, writing nothing. */
    public function rehearseAdopting(): void
    {
        $this->following = null;
        $this->rehearsingAnAdoption = true;
        $this->adoptionRehearsed = false;
        $this->going = $this->put(
            fn(Stack $stack, Session $session): WhatBecameOfTheWiring => $this->wiring->rehearseAdopting($stack, $session),
        );
    }

    /** Adopt them, only against the rehearsal on the screen. */
    public function adopt(): void
    {
        if (! $this->adoptionRehearsed) {
            return;
        }

        $this->following = null;
        $this->rehearsingAnAdoption = false;
        $this->adoptionRehearsed = false;
        $this->going = $this->put(
            fn(Stack $stack, Session $session): WhatBecameOfTheWiring => $this->wiring->adopt($stack, $session),
        );
    }
}
