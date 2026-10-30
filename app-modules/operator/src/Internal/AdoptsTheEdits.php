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
 * The screen that uses it follows the run the way it follows a wiring run, and reads where adopting stands when the run answers.
 *
 * @phpstan-require-extends NativeComponent
 */
trait AdoptsTheEdits
{
    /** Where adopting stands, while a rehearsal of it is followed or on the screen. */
    public ?WhereAdoptingStands $adopting = null;

    /** Ask what adopting the operator's own edits would keep and wire, writing nothing. */
    public function rehearseAdopting(): void
    {
        $this->following = null;
        $this->adopting = WhereAdoptingStands::Rehearsing;
        $this->going = $this->put(
            fn(Stack $stack, Session $session): WhatBecameOfTheWiring => $this->wiring->rehearseAdopting($stack, $session),
        );
    }

    /** Adopt them, only against the rehearsal on the screen. */
    public function adopt(): void
    {
        if ($this->adopting !== WhereAdoptingStands::Rehearsed) {
            return;
        }

        $this->following = null;
        $this->adopting = null;
        $this->going = $this->put(
            fn(Stack $stack, Session $session): WhatBecameOfTheWiring => $this->wiring->adopt($stack, $session),
        );
    }
}
