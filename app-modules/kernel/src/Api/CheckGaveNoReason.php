<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use InvalidArgumentException;

/**
 * A check produced no verdict and said nothing about why.
 *
 * Refused rather than shown, for the reason {@see CheckSaidNothing} gives one
 * outcome over: a row the operator cannot act on and cannot search for is a
 * fault in the core, and the fault belongs where the payload is read.
 *
 * Sharper here than there, because of what these two outcomes are for. A check
 * that could not run is its own outcome rather than a level of severity so that
 * it is never mistaken for one that passed — and a row that says only "could
 * not be established", with no sentence, is read by an operator as a check that
 * found nothing wrong.
 *
 * No code is named, unlike {@see CheckSaidNothing}: these outcomes carry none,
 * which is the whole reason they are a separate arm.
 */
final class CheckGaveNoReason extends InvalidArgumentException
{
    public static function forNotRunning(): self
    {
        return new self(
            'A check produced no verdict and carried no reason, so nothing could be shown about why it has no answer.',
        );
    }
}
