<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Asking a stack what is already on its machine, before anything is moved in.
 *
 * A read. It chooses no mode and moves nothing: the survey is the look that
 * changes nothing, and choosing is a separate act. It takes a stack and a
 * session rather than a client, for the reason {@see Asking} gives.
 */
interface MovingIn
{
    /** Ask a stack what it found already standing, or come away with a reason (`C1`). */
    public function surveyedOn(Stack $stack, Session $session): WhatWasFoundAlreadyHere;
}
