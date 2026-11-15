<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack to wake this phone through its endpoint, and to stop.
 *
 * The operator's alone. Each takes a stack and a session, for the reason {@see Asking} gives.
 */
interface RegisteringAWake
{
    /** Ask the stack to keep the endpoint, and answer what it said. */
    public function registeredOn(Stack $stack, Session $session, AWakeToRegister $wake): WhatRegisteringAWakeCameTo;

    /** Ask the stack to forget the endpoint of that name, and answer what it said. */
    public function removedFrom(Stack $stack, Session $session, APushName $name): WhatRemovingAWakeCameTo;
}
