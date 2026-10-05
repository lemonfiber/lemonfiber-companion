<?php

declare(strict_types=1);

namespace Modules\Services\Api\Queries;

use Modules\Kernel\Api\Daemons;

/**
 * The services a stack runs, less the ones the forms asked for and the stack left out.
 *
 * The stack lists a service the forms left out among its services, as absent,
 * and again among what was left out, with what it would need. Read as a
 * service, it is one that failed; read as left out, it is filtered and says
 * why. So it is read once, as left out: the listing this answers keeps how the
 * stack is running, what each verb takes away, the forms asked for and what
 * was left out, and holds every other service in the order the stack sent.
 */
final readonly class WithoutWhatWasLeftOut
{
    public function over(Daemons $daemons): Daemons
    {
        $kept = [];

        foreach ($daemons as $daemon) {
            if (! $daemons->leftOut()->include($daemon->id())) {
                $kept[] = $daemon;
            }
        }

        // Spread rather than handed over as an array, which a published
        // signature does not take.
        return Daemons::of($daemons->running(), $daemons->disturbs(), ...$kept)->asked($daemons->active(), $daemons->leftOut());
    }
}
