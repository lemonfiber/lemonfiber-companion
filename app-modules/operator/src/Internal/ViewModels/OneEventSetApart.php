<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One event set apart from the preset, flattened for a template. */
final readonly class OneEventSetApart
{
    /**
     * @param string $kind      the event, as a finding names it
     * @param string $heardSaid the catalogue key for whether it is heard about
     */
    public function __construct(
        public string $kind,
        public string $heardSaid,
    ) {}
}
