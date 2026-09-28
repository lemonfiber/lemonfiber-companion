<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A run the operator agreed to put back, having been shown what goes with it.
 *
 * {@see PuttingARunBack::putBack()} takes one of these and nothing else, and
 * the only way to make one is from {@see ARunToPutBack} — the record's own
 * rows for that run. So a yes follows what the operator was shown, and a run
 * the record holds nothing of, or one it says cannot go back, has nothing to
 * agree to.
 *
 * It carries the stamp and nothing further, because the stack takes nothing
 * further: naming any change of a run names the whole run.
 */
final readonly class ARunAgreedTo
{
    private function __construct(private ARun $run) {}

    /** The run the record showed; one it holds nothing of, or that cannot go back, is refused. */
    public static function by(ARunToPutBack $shown): self
    {
        if (! $shown->goesBack()) {
            throw ThereIsNothingToAgreeTo::aRun($shown->run());
        }

        return new self($shown->run());
    }

    /** The run agreed to, by its stamp. */
    public function run(): ARun
    {
        return $this->run;
    }
}
