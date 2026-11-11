<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack when its machine last started and what the check after it came to.
 *
 * It takes a stack and a session rather than a client, for the reason
 * {@see Asking} gives.
 */
interface AskingHowItCameBack
{
    /** Ask a stack about its last start, or come away with a reason. */
    public function afterTheLastStart(Stack $stack, Session $session): HowItCameBack;
}
