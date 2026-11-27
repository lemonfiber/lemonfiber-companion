<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Where what a setting falls back to comes from, by the stack's word for it.
 */
enum WhereADefaultComesFrom: string
{
    /** One of the core's own settings, with what the core reads while none is recorded. */
    case TheCore = 'core';
    /** The one default every reader whose needs are met declares. */
    case Declared = 'declared';
    /** Each reader's own, where the readers whose needs are met declare different ones. */
    case EachReader = 'per-reader';
    /** Nothing declares one, so nothing is written for it. */
    case Nowhere = 'none';
}
