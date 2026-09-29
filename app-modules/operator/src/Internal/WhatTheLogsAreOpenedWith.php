<?php

declare(strict_types=1);

namespace Modules\Operator\Internal;

/**
 * What a road into a service's logs hands the log screen besides the route.
 *
 * A code is for whoever helps the operator, so it is not on the card that
 * leads there: it travels with the road and is said above the lines, where
 * somebody reading them to find out what happened is already looking. Each
 * case is the key it travels under.
 */
enum WhatTheLogsAreOpenedWith: string
{
    /** The code a check reported about the service. */
    case Reported = 'reported';

    /** The exit code the service stopped with. */
    case Exited = 'exited';

    /**
     * What a road carries for this code, or nothing where there is none.
     *
     * @return array<string, string>
     */
    public function carrying(string $code): array
    {
        return $code === '' ? [] : [$this->value => $code];
    }
}
