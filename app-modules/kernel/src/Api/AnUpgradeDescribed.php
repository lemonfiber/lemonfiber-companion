<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * An upgrade the stack described without carrying out, which the operator may agree to.
 *
 * {@see UpgradingTheLibrary::upgrade()} takes one of these and nothing else,
 * and the only way to make one is from a description. So the yes follows what
 * the operator was shown kind by kind, and reading a description builds
 * nothing a port would carry out.
 *
 * It carries nothing further because the wire takes nothing further: the
 * stack upgrades each kind to the preset in force for it, which is what the
 * description said.
 */
final readonly class AnUpgradeDescribed
{
    private function __construct()
    {
        // Nothing to hold: being built at all, by `by()`, is the whole proof.
    }

    /** The upgrade the stack described; one already carried out is refused. */
    public static function by(TheUpgrade $described): self
    {
        if ($described->wasCarriedOut()) {
            throw ThereIsNothingToAgreeTo::described();
        }

        return new self();
    }
}
