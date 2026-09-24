<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * A declared monthly cap, flattened for a template.
 *
 * A cap of nothing has a figure of nought and is still an instance; a stack
 * with no cap declared has none, so the two cannot be drawn as each other.
 */
final readonly class TheCapAsShown
{
    /**
     * @param int    $figure       the allowance, as a whole figure of its unit
     * @param string $unit         the catalogue key for that unit
     * @param string $doesSaid     the catalogue key for pause, throttle or continue
     * @param string $standingSaid the catalogue key for where the month stands, or empty where nothing counted it
     */
    public function __construct(
        public int $figure,
        public string $unit,
        public string $doesSaid,
        public string $standingSaid,
    ) {}
}
