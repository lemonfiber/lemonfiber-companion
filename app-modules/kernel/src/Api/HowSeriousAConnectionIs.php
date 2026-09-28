<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * How serious a connection's outcome is, in the stack's word for it.
 *
 * Drift is usually the operator's own harmless edit, so it is information; a
 * warning is a connection that breaks the stack, and it always carries what
 * breaks and what would put it right ({@see WhatItWouldBreak}).
 */
enum HowSeriousAConnectionIs: string
{
    /** Nothing is broken. */
    case Informational = 'informational';

    /** The connection breaks the stack. */
    case Warning = 'warning';
}
