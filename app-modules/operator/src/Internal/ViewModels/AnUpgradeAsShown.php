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
     * @param string $sizePerHour roughly what an hour of it costs at that preset
     * @param string $askingSaid  the catalogue key for what became of asking its service
     * @param string $detail      the service's own account of why it refused, or empty
     */
    public function __construct(
        public string $kind,
        public string $preset,
        public string $sizePerHour,
        public string $askingSaid,
        public string $detail,
    ) {}
}
