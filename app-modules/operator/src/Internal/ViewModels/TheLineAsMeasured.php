<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * What the line was measured to carry, flattened for a template.
 *
 * Every field is set, because the value it comes from refuses to be built
 * without it. Where nothing measured the line there is no instance at all, and
 * the template says so rather than drawing zeros.
 */
final readonly class TheLineAsMeasured
{
    /**
     * @param int    $downFigure   bytes a second down, as a whole figure of its unit
     * @param string $downUnit     the catalogue key for that unit
     * @param int    $upFigure     bytes a second up, as a whole figure of its unit
     * @param string $upUnit       the catalogue key for that unit
     * @param string $measuredSaid the catalogue key for declared or observed
     * @param string $tunnelSaid   the catalogue key for through the tunnel or beside it
     * @param string $agoSaid      the catalogue key for how long ago it was taken
     * @param int    $agoCount     how many of that unit
     */
    public function __construct(
        public int $downFigure,
        public string $downUnit,
        public int $upFigure,
        public string $upUnit,
        public string $measuredSaid,
        public string $tunnelSaid,
        public string $agoSaid,
        public int $agoCount,
    ) {}
}
