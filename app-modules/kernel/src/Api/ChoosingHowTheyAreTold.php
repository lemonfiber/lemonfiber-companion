<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/** Which of the ways the operator offered the household tell a member that something they asked for has arrived. */
interface ChoosingHowTheyAreTold
{
    /** The ways offered, each with whether this member is told on it. */
    public function offered(Stack $stack, Session $session): HowBeingToldCameBack;

    /**
     * Be told on exactly these ways, by their ids, and none other.
     *
     * @param list<string> $ways
     */
    public function choose(Stack $stack, Session $session, array $ways): HowBeingToldCameBack;
}
