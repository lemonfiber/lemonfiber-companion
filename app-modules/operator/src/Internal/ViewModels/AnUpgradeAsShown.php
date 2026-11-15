<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/**
 * One kind of media an upgrade covers, flattened for a template.
 */
final readonly class AnUpgradeAsShown
{
    /**
     * @param string $kind        the kind of media, as the stack writes it
     * @param string $preset      the preset in force for it
     * @param string $totalSaid   the catalogue key for what it takes in total, or why that is not known
     * @param int    $figure      what it takes, in the unit beside it, or nothing where that is not known
     * @param string $unit        the catalogue key for the unit the figure is in, or empty
     * @param string $why         the stack's reason the total is not known, or empty
     * @param string $askingSaid  the catalogue key for what became of asking its service
     * @param string $detail      the service's own account of why it refused, or empty
     */
    public function __construct(
        public string $kind,
        public string $preset,
        public string $totalSaid,
        public int $figure,
        public string $unit,
        public string $why,
        public string $askingSaid,
        public string $detail,
    ) {}
}
