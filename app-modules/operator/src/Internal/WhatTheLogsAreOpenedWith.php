<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

/**
 * What a road into a service's logs hands the log screen besides the route.
 *
 * A code is for whoever helps the operator, so it is not on the card that
 * leads there: it travels with the road and is said above the lines, where
 * somebody reading them to find out what happened is already looking. The
 * service's name travels the same way, for the heading. Each case is the key
 * it travels under.
 */
enum WhatTheLogsAreOpenedWith: string
{
    /** The code a check reported about the service. */
    case Reported = 'reported';

    /** The exit code the service stopped with. */
    case Exited = 'exited';

    /**
     * What the stack calls the service, which the route cannot say: the route
     * names it by its id, which is what the logs are asked for with.
     */
    case Called = 'called';

    /**
     * What a road carries for this value, or nothing where there is none.
     *
     * @return array<string, string>
     */
    public function carrying(string $value): array
    {
        return $value === '' ? [] : [$this->value => $value];
    }
}
