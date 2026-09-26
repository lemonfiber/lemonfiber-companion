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
    ) {}

    public static function nothingStoppedIt(): self
    {
        return new self('', '');
    }

    public static function because(Obstacle $why): self
    {
        return new self($why->said(), $why->remedy());
    }
}
