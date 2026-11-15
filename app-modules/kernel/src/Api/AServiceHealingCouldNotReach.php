<?php

declare(strict_types=1);

namespace Modules\Kernel\Api;

/**
 * A curator or download client the queue's latest pass could not reach or verify, and why.
 */
final readonly class AServiceHealingCouldNotReach
{
    private function __construct(private ServiceId $service, private string $why) {}

    /** This service, for this reason in the stack's words. */
    public static function because(ServiceId $service, string $why): self
    {
        return new self($service, $why);
    }

    /** The service. */
    public function service(): ServiceId
    {
        return $this->service;
    }

    /** Why, in the stack's words. */
    public function why(): string
    {
        return $this->why;
    }
}
