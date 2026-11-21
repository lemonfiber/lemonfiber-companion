<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/** Every decision setup makes on a stack, held or not, asked of the stack. */
interface AskingWhatSetupDecided
{
    public function decidedOn(Stack $stack, Session $session): WhatWasFoundOfTheDecisions;
}
