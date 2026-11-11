<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * One run of the check the stack makes after the machine starts: when it ran, and what it came to.
 */
final readonly class ACheckAfterAStart
{
    private function __construct(
        private Instant $at,
        private HowTheCheckAfterAStartEnded $ended,
        private TheServicesNamed $notBack,
        private TheTunnelAfterAStart $tunnel,
        private ThePortAfterAStart $port,
        private string $said,
    ) {}

    /**
     * A run that finished at `$at`.
     *
     * `$said` is the sentence the run raised, or empty where it raised none.
     */
    public static function ran(
        Instant $at,
        HowTheCheckAfterAStartEnded $ended,
        TheServicesNamed $notBack,
        TheTunnelAfterAStart $tunnel,
        ThePortAfterAStart $port,
        string $said,
    ): self {
        return new self($at, $ended, $notBack, $tunnel, $port, $said);
    }

    /** When it finished. */
    public function at(): Instant
    {
        return $this->at;
    }

    /** What it came to. */
    public function ended(): HowTheCheckAfterAStartEnded
    {
        return $this->ended;
    }

    /** Each service that did not come back. */
    public function notBack(): TheServicesNamed
    {
        return $this->notBack;
    }

    /** Whether the tunnel came back. */
    public function tunnel(): TheTunnelAfterAStart
    {
        return $this->tunnel;
    }

    /** The forwarded port before the start and after it. */
    public function port(): ThePortAfterAStart
    {
        return $this->port;
    }

    /** The sentence it raised, or empty where it raised none. */
    public function said(): string
    {
        return $this->said;
    }
}
