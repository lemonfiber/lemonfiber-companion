<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A preview of putting the configuration back, which the operator said yes to.
 *
 * {@see ResettingTheConfiguration::revert()} takes one of these and nothing
 * else, and the only way to make one is from a preview that would revert
 * something. So the yes follows what the operator was shown file by file, and
 * reading a preview builds nothing a port would carry out.
 *
 * It carries nothing further because the wire takes nothing further: the
 * stack reverts every edit it finds when the yes arrives, and its report says
 * which those were.
 */
final readonly class AResetAgreed
{
    private function __construct()
    {
        // Nothing to hold: being built at all, by `to()`, is the whole proof.
    }

    /** The preview agreed to; one carried out, or one reverting nothing, is refused. */
    public static function to(TheReset $previewed): self
    {
        if ($previewed->wasCarriedOut()) {
            throw ThereIsNothingToAgreeTo::reverted();
        }

        if ($previewed->changesNothing()) {
            throw ThereIsNothingToAgreeTo::nothingToRevert();
        }

        return new self();
    }
}
