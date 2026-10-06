<?php

declare(strict_types=1);

namespace Modules\Household\Internal;

use Modules\Kernel\Api\StackId;
use Modules\Stacks\Api\AStacksScreen;

/**
 * Where one house's screens live, for the member's own surface.
 *
 * The paths are {@see AStacksScreen}'s; this is the half that knows *which
 * house*, built from the stack a screen is already about, so a member's tab
 * cannot lead to another house's.
 */
final readonly class WhereTheHouseIs
{
    private function __construct(private StackId $stack) {}

    /** A house this phone holds. */
    public static function of(StackId $stack): self
    {
        return new self($stack);
    }

    /** One of this house's screens that naming the house is enough to reach. */
    public function to(AStacksScreen $screen): string
    {
        return $screen->forTheStack($this->stack);
    }
}
