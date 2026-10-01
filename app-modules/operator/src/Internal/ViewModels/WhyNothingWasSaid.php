<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

use Modules\Kernel\Api\Obstacle;

/** What stopped a subscription, as the two keys a template draws, or nothing. */
final readonly class WhyNothingWasSaid
{
    private function __construct(
        public string $met,
        public string $remedy,
        private ?Obstacle $why = null,
    ) {}

    public static function nothingStoppedIt(): self
    {
        return new self('', '');
    }

    public static function because(Obstacle $why): self
    {
        return new self($why->said(), $why->remedy(), $why);
    }

    /** Whether what stood in the way is put right on this app's page in the phone's settings. */
    public function isPutRightInTheAppsSettings(): bool
    {
        return $this->why instanceof Obstacle && $this->why->isPutRightInTheAppsSettings();
    }

    /**
     * What both sentences are filled with: the facts the obstacle was met with, or nothing.
     *
     * @return array<string, int>
     */
    public function filling(): array
    {
        return $this->why instanceof Obstacle ? WhatAnObstacleNames::in($this->why) : [];
    }
}
