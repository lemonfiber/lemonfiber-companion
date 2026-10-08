<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

use function trim;

/**
 * Something the stack asks for that an install would leave with more than one service claiming it.
 *
 * Answering it is the wiring screen's: until somebody chooses, the ask is refused.
 */
final readonly class ACapabilityLeftContested
{
    private function __construct(private string $capability, private string $by, private PluginLines $claimants) {}

    /** The contest; a blank capability is refused. */
    public static function over(string $capability, string $by, PluginLines $claimants): self
    {
        if (trim($capability) === '') {
            throw PluginSaysNothing::about('capability');
        }

        return new self($capability, trim($by), $claimants);
    }

    /** What is asked for. */
    public function capability(): string
    {
        return $this->capability;
    }

    /** What asks for it, or empty. */
    public function by(): string
    {
        return $this->by;
    }

    /** Every service that would claim it. */
    public function claimants(): PluginLines
    {
        return $this->claimants;
    }
}
