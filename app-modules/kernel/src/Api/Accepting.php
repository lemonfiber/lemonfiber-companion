<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Accepting a warning the stack raises about a choice, so it stops leading while it stays on the report, and following it.
 */
interface Accepting
{
    /** Accept the warning this check raises. */
    public function accept(Stack $stack, Session $session, Check $check): WhatBecameOfTheAcceptance;

    /** What became of it, by the handle it answered. */
    public function whatBecameOf(Stack $stack, Session $session, Job $job): WhatBecameOfTheAcceptance;
}
