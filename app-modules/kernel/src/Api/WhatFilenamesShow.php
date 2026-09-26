<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * Whether a support bundle shows media filenames as they are.
 *
 * Replaced unless the operator asks otherwise. A library's contents are not a
 * credential, and they are the one thing in a bundle that says something about
 * the person rather than the machine.
 */
enum WhatFilenamesShow: string
{
    /** Each filename replaced by a mark that keeps two mentions of one file recognisable. */
    case Replaced = 'replaced';

    /** Each filename as it is, because the operator asked for them. */
    case Shown = 'shown';
}
