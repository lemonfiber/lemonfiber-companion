<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/** Asking a machine where its alerts go, which only the operator may. */
interface KnowingWhereAlertsGo
{
    /** Where the machine's alerts go, or what stood in the way; a store the core cannot read is a refusal, never no back-ends. */
    public function whereAlertsGoOn(Stack $stack, Session $session): WhereAlertsGoCameBack;
}
