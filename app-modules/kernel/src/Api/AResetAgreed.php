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
 * It carries the name the stack gave the preview, which the yes quotes, so
 * the stack refuses a yes given for files that have moved since rather than
 * revert what it finds now.
 */
final readonly class AResetAgreed
{
    private function __construct(private string $offer) {}

    /** The preview agreed to; one carried out, or one reverting nothing, is refused. */
    public static function to(TheReset $previewed): self
    {
        if ($previewed->wasCarriedOut()) {
            throw ThereIsNothingToAgreeTo::reverted();
        }

        if ($previewed->changesNothing()) {
            throw ThereIsNothingToAgreeTo::nothingToRevert();
        }

        return new self($previewed->offer());
    }

    /** The name of the preview agreed to, which the yes quotes. */
    public function offer(): string
    {
        return $this->offer;
    }
}
