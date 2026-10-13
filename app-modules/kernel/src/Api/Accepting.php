<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Accepting a warning the stack raises about a choice, so it stops leading while it stays on the report, and following it.
 */
interface Accepting
{
    /**
     * Accept the warning this check raises.
     *
     * @return WhatBecameOfTheWork<Report>
     */
    public function accept(Stack $stack, Session $session, Check $check): WhatBecameOfTheWork;

    /**
     * What became of it, by the handle it answered.
     *
     * @return WhatBecameOfTheWork<Report>
     */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheWork;
}
