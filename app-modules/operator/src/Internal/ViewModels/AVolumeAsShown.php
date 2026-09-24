<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One volume, flattened for a template.
 *
 * A figure the stack could not read is null, and the template says so in words
 * rather than drawing nought. `$agoSaid` is empty for a live reading, and the
 * catalogue key for how long ago a network share was last read otherwise.
 */
final readonly class AVolumeAsShown
{
    /**
     * @param string $holdsSaid  the catalogue key for which volume this is
     * @param string $point      where it is mounted, or empty where the stack could not say
     * @param string $standsSaid the catalogue key for where it stands
     * @param string $agoSaid    the catalogue key for how old the reading is, or empty where it is live
     * @param int    $agoCount   how many of that unit ago
     */
    public function __construct(
        public string $holdsSaid,
        public string $point,
        public string $standsSaid,
        public ?ASizeAsShown $free,
        public ?ASizeAsShown $limit,
        public ASizeAsShown $committed,
        public ?ASizeAsShown $projected,
        public string $agoSaid,
        public int $agoCount,
    ) {}
}
