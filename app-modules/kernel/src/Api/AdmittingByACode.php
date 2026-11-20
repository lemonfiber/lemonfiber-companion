<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Signing a member in by a code a service outside the house approves.
 */
interface AdmittingByACode
{
    /** Ask the stack for a code to approve. */
    public function askForACode(Stack $stack): CodeAsked;

    /** Ask the stack whether the code under this handle was approved, and come away with the member's session once it was. */
    public function finishByCode(Stack $stack, AWaitingCode $waiting): HowTheCodeStands;
}
