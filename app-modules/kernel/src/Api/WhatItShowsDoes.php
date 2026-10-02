<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Whether what a screen shows changes while it is open, without anybody touching the phone.
 *
 * A screen whose content can change while open refreshes on a cadence it
 * declares, and a screen whose content cannot must not poll. Every screen says
 * which it is, through {@see ItsContent}, so a rule can hold each to its own
 * answer.
 */
enum WhatItShowsDoes
{
    /** It moves on its own, so the screen looks again on a declared cadence or listens for it. */
    case ChangesOnItsOwn;

    /** It changes only when the operator acts, so the screen never looks again by itself. */
    case ChangesOnlyWhenAsked;
}
