<?php

declare(strict_types=1);

namespace Modules\Operator\Internal\ViewModels;

/** One port a verb wanted that something else holds, flattened to a row. */
final readonly class APortHeldAsShown
{
    /**
     * @param string $port     the host port, as a figure
     * @param string $wantedBy the lemonfiber service that wants it
     * @param string $heldBy   what already holds it
     */
    public function __construct(
        public string $port,
        public string $wantedBy,
        public string $heldBy,
    ) {}
}
